const puppeteer = require('puppeteer');

console.log('Puppeteer version:', require('puppeteer').version);

const tiendas = [
  {
    nombre: 'Syscom',
    url: (q) => `https://www.syscom.mx/buscar.html?query=${encodeURIComponent(q)}`,
    selectorFila: '.product-list .product',
    extraer: async (page) => {
      return await page.$$eval('.product-list .product', filas => filas.slice(0,3).map(fila => {
        const nombre = fila.querySelector('.product-title')?.innerText.trim() || '';
        const precio = fila.querySelector('.product-price')?.innerText.replace(/[^\d.,]/g, '').replace(',', '') || '';
        const enlace = fila.querySelector('a')?.href || '';
        return { nombre, precio, enlace };
      }));
    }
  },
  {
    nombre: 'TVC.mx',
    url: (q) => {
      const queryObj = {
        search: q,
        view: "grid",
        sortBy: { sortCriteria: "name", sortDirection: "desc" },
        perPage: 60,
        priceRange: { min: 0, max: null }
      };
      return "https://tvc.mx/products?categoryId=&query=" + encodeURIComponent(JSON.stringify(queryObj));
    },
    extraer: async (page) => {
      return await page.$$eval('.product-card', filas => filas.slice(0,3).map(fila => {
        const nombre = fila.querySelector('.product-title span')?.innerText.trim() || '';
        const precio = fila.querySelector('.product-price')?.innerText.replace(/[^\d.,]/g, '').replace(',', '') || '';
        const enlace = fila.querySelector('.product-title a')?.href
          ? 'https://tvc.mx' + fila.querySelector('.product-title a').getAttribute('href')
          : '';
        const sku = fila.querySelector('.product-description')?.innerText.trim() || '';
        return { nombre, precio, enlace, sku };
      }));
    }
  },
  {
    nombre: 'Tecnosinergia',
    url: (q) => {
      if (q.startsWith('http')) return q;
      return `https://tecnosinergia.com/catalogsearch/result/?q=${encodeURIComponent(q)}`;
    },
    extraer: async (page) => {
      return await page.$$eval('.product-item-info', filas => filas.slice(0,3).map(fila => {
        const nombre = fila.querySelector('.product-item-link')?.innerText.trim() || '';
        const descripcion = fila.querySelector('.product-item-details .product-item-description')?.innerText.trim() || '';
        let sku = '';
        const skuElem = Array.from(fila.querySelectorAll('.product.attribute.sku, .sku')).find(el => el.innerText);
        if (skuElem) {
          sku = skuElem.innerText.replace('SKU:', '').trim();
        }
        return { nombre, descripcion, sku };
      }));
    }
  },
  {
    nombre: 'PCH',
    url: (q) => `https://shop.pchconnect.com/productos?search=${encodeURIComponent(q)}&orderBy=stock`,
    extraer: async (page) => {
      return await page.$$eval('.product-wrap', filas => filas.slice(0,3).map(fila => {
        const nombre = fila.querySelector('.product-name a')?.innerText.trim() || '';
        const precio = fila.querySelector('.product-price ins.new-price')?.innerText.replace(/[^\d.,]/g, '').replace(',', '') || '';
        const enlace = fila.querySelector('.product-name a')?.href || '';
        let sku = '';
        const skuDiv = Array.from(fila.querySelectorAll('.text-start')).find(div => div.textContent.includes('SKU:'));
        if (skuDiv) {
          sku = skuDiv.textContent.replace('SKU:', '').trim();
        }
        let stock = '';
        const stockDiv = Array.from(fila.querySelectorAll('div')).find(div => div.textContent.includes('Stock Disponible:'));
        if (stockDiv) {
          stock = stockDiv.innerText.replace('Stock Disponible:', '').trim();
        }
        return { nombre, precio, enlace, sku, stock };
      }));
    }
  }
];

(async () => {
  const query = process.argv.slice(2).join(' ');
  if (!query) {
    console.error('Debes proporcionar un nombre o SKU para buscar.');
    process.exit(1);
  }
  const browser = await puppeteer.launch({ headless: true });
  const resultados = [];
  for (const tienda of tiendas) {
    const page = await browser.newPage();
    try {
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
      await page.goto(tienda.url(query), { waitUntil: 'domcontentloaded', timeout: 20000 });
      await page.evaluate(() => new Promise(resolve => setTimeout(resolve, 8000)));
      await page.screenshot({ path: `screenshot_${tienda.nombre}.png` });
      const productos = await tienda.extraer(page);
      resultados.push({ tienda: tienda.nombre, resultados: productos });
    } catch (e) {
      resultados.push({ tienda: tienda.nombre, resultados: [], error: e.message });
    }
    await page.close();
  }
  await browser.close();
  console.log(JSON.stringify(resultados));
})(); 