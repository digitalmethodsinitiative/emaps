import json
from scrapy.spider import Spider
from undp.items import UndpItem

class CIGraspSpider(Spider):
    name = 'cigrasp'
    base_url = 'http://www.pik-potsdam.de/~wrobel/ci_2/adaptation_projects/'

    # Loading the json file to scrap
    def __init__(self):
        with open('feed/cigrasp.json', 'r') as jf:
            pl = json.load(jf)
            self.project_list = {p['url']: p for p in pl}
            self.start_urls = [p['url'] for p in pl]
    
    # Parsing the response
    def parse(self, response):
        yield UndpItem(
            url=response.url,
            meta=self.project_list[response.url],
            html=response.body.decode(response.encoding or 'utf-8', errors='replace')
        )
