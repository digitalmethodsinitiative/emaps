import json
from scrapy.spider import Spider
from undp.items import UndpItem

class ProjectsSpider(Spider):
    name = 'projects'
    base_url = 'http://www.undp-alm.org/projects/'

    # Loading the json file to scrap
    def __init__(self):
        with open('feed/undp.json', 'r') as jf:
            pl = json.load(jf)
            self.project_list = {p['url']: p for p in pl}
            self.start_urls = [self.base_url + p['url'] for p in pl]
    
    # Parsing the response
    def parse(self, response):
        yield UndpItem(
            url=response.url,
            meta=self.project_list[response.url.replace(self.base_url, '')],
            html=response.body
        )
