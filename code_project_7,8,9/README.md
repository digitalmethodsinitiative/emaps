Scripts written by Guillaume PLIQUE <guillaume.plique@sciencespo.fr>

# UNDP & CIGRASP Database scraping

I have no idea whether the process can be run again flawlessly because both source sites might have changed from the last time.

## Languages

* python 2.7
* ruby
* node.js

## Databases

* mongodb (on default localhost, port 27017)

If you need to change the database location, the lines to modify are:

* `undp/pipelines.py/L8`
* `scripts/scrap_cigrasp.js/L14`
* `scripts/scrap_undp.js/L10`

## Dependencies installation

```bash
# Python dependencies (in a virtualenv if possible)
pip install scrapy pymongo

# Node dependencies
npm install cheerio mongodb
```

## Workflow

### Retrieving the needed HTML with python & scrapy

```bash
# UNDP
scrapy crawl projects
# CIGRASP
scrapy crawl cigrasp
```

### Parsing HTML from Mongodb & node.js

```bash
# UNDP
node scrips/scrap_undp.js
# CIGRASP
node scripts/scrap_cigrasph.js
```

This will output a file named `data.json` for undp and `cigrasp_final.json` in the root directory.

### Querying

The queries are run in ruby. A file named `query.rb` exists in the root directory and has an help message

```bash
ruby query.rb help
```

A wide array of other queries and merging are made by the ruby scripts in the `scripts` folder.

## Folders

* **data** : containing final data files.
* **feed** : temporary data files needed for intermediate tasks.
* **output** : output files, created typically by queries.
* **scripts** : several ruby and node.js scripts.
* **undp** : root of the scrapy files.
