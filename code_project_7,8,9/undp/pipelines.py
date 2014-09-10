# Define your item pipelines here
#
# Don't forget to add your pipeline to the ITEM_PIPELINES setting
# See: http://doc.scrapy.org/en/latest/topics/item-pipeline.html
from pymongo import MongoClient

class MongoPipeline(object):
    client = MongoClient('localhost', 27017)
    db = client.cigrasp

    def process_item(self, item, spider):
        self.db.output.insert(dict(item))
        return item
