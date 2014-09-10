var fs = require('fs'),
    cheerio = require('cheerio'),
    MongoClient = require('mongodb').MongoClient;

// Output var
var scraped = [],
    db;

// Connection to db
MongoClient.connect('mongodb://127.0.0.1:27017/undp', function(err, database) {
  db = database;
  var collection = db.collection('output');
  collection.find().toArray(onFind)
});

// On find
function onFind(err, results) {

  // Scraping
  results.map(function(r) {
    scraped.push(scrap(r));
  });

  // Writing to file
  fs.writeFileSync('data.json', JSON.stringify(scraped, undefined, 2), 'utf-8');

  // Closing connection
  db.close();
}

// Scraping function
function scrap(r) {
  var $ = cheerio.load(r.html),
      data = {nap: !!r.meta.title.match(/\bNAP\b/),
              'p-cba': !!r.meta.title.match(/\bP-CBA\b/)};

  if (data.nap || data['p-cba']) {
    data.content = $('.field-item').first().html();
  }
  else {
    data.summary = $('.field-type-text-with-summary').find('.field-item.even').html();
    $('.field-label').each(function() {
      var field = $(this).parent().attr('class').split(' ').filter(function(c) {
        return ~c.indexOf('field-name-field-');
      })[0].replace('field-name-field-', '');

      var $items = $(this).next().children('.field-item');
      data[field] = ($items.length > 1) ?
        $items.get().map(function(e) {
          return $(e).text();
        }) :
        $items.text();
    });
  }

  return {
    url: r.url,
    identifier: r.url.split('/').slice(-1)[0],
    title: r.meta.title,
    funding: r.meta.funding,
    theme: r.meta.theme,
    location: r.meta.location,
    data: data
  }
}