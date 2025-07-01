const puppeteer = require('puppeteer');

const tiendas = [
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
  },
  {
    nombre: 'Syscom',
    url: (q) => `https://www.syscom.mx/buscar.html?query=${encodeURIComponent(q)}`,
    extraer: async (page, query) => {
      return [];
    }
  },
  {
    nombre: 'TVC.mx',
    url: (q) => `https://tvc.mx/buscar?q=${encodeURIComponent(q)}`,
    extraer: async (page, query) => {
      return [];
    }
  },
  {
    nombre: 'Tecnosinergia',
    url: (q) => `https://tecnosinergia.com/catalogsearch/result/?q=${encodeURIComponent(q)}`,
    extraer: async (page, query) => {
      return [];
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
      let productos = [];
      if (tienda.nombre === 'PCH') {
        productos = await tienda.extraer(page);
      }
      resultados.push({ tienda: tienda.nombre, resultados: productos });
    } catch (e) {
      resultados.push({ tienda: tienda.nombre, resultados: [], error: e.message });
    }
    await page.close();
  }
  await browser.close();
  console.log(JSON.stringify(resultados));
})(); 