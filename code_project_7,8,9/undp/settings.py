# Scrapy settings for undp project
#
# For simplicity, this file contains only the most important settings by
# default. All the other settings are documented here:
#
#     http://doc.scrapy.org/en/latest/topics/settings.html
#

BOT_NAME = 'undp'

SPIDER_MODULES = ['undp.spiders']
NEWSPIDER_MODULE = 'undp.spiders'

CONCURRENT_REQUESTS_PER_DOMAIN = 1
ITEM_PIPELINES = [
    'undp.pipelines.MongoPipeline'
]
AUTOTHROTTLE_ENABLED = True
# Crawl responsibly by identifying yourself (and your website) on the user-agent
#USER_AGENT = 'undp (+http://www.yourdomain.com)'
