var fs = require('fs'),
    cheerio = require('cheerio'),
    MongoClient = require('mongodb').MongoClient;

// Output var
var scraped = [],
    db;

// TODO: split the type into arrays



// Connection to db
MongoClient.connect('mongodb://127.0.0.1:27017/cigrasp', function(err, database) {
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
  fs.writeFileSync('cigrasp_final.json', JSON.stringify(scraped, undefined, 2), 'utf-8');

  // Closing connection
  db.close();
}

function slug(s) {
  return s.replace(/ /g, '_').replace(':', '');
}

function clean(s) {
  return s.trim().replace('<i>', '').replace('</i>', '').replace('<p></p>', '');
}

// Scraping function
function scrap(r) {
  var $ = cheerio.load(r.html.replace('<br>>', '<br>'));

  function scrapEasy(title, s) {
    var $p = $('legend:contains("' + title + '")'),
        $pt = $p.next().children('tr');
    data[s] = {};

    $pt.each(function() {
      var $i = $(this).children('td');
      var field = $i.first().text().trim();

      if (field)
        data[s][slug(field)] = clean($i.eq(1).html());
    });
  }

  function scrapEasier(title, s) {
    var $a = $('legend:contains("' + title + '")'),
      $at = $a.next().find('td').first();

    data[s] = clean($at.html());
  }

  var data = {
    url: r.url,
    identifier: +r.meta.identifier,
    title: r.meta.title,
    continent: r.meta.continent,
    country: r.meta.country,
    scale: r.meta.scale,
    types: r.meta.type.split(', ')
  };

  // Overview
  data.overview = {
    sector: $('td:contains("sector:")').next().text().replace('\n', ''),
    stimuli: $('td:contains("stimulus:")').next().text().replace('\n', '').split(', '),
    impacts: $('td:contains("impacts:")').next().text().replace('\n', '').split(', '),
    feedback: r.html.match(/var longText = "(.*)";/)[1].replace("<a href='javascript:updateDesc(0)'>... read less</a>", '').replace(/<[\/!]?\[?[a-z0-9\-]+[^>]*>/g, '')
  };

  // Project classification
  var $pc = $('legend:contains("project classification:")'),
      $pct = $pc.next().children('tr');
  data.project_classification = {};

  $pct.each(function() {
    var $i = $(this).children('td');
    var field = $i.first().text().trim();

    if (field)
      data.project_classification[slug(field)] = $i.eq(1).html().trim().replace('<i>', '').replace('</i>', '');

    if (field === 'project type:')
      data.project_classification['project_type'] = data.project_classification['project_type'].split(', ');
  });

  // Other
  scrapEasy('project costs:', 'project_costs');
  scrapEasy('problem solving capacity and reversibility:', 'problem_solving_capacity_an_reversibility');
  scrapEasy('responsibilities:', 'responsibilities');
  scrapEasy('evaluative information:', 'evaluative_information');

  // Additional information
  scrapEasier('additional information:', 'additional_information');
  scrapEasier('contact information:', 'contact_information');
  scrapEasier('information source:', 'information_source');

  return data;
}
