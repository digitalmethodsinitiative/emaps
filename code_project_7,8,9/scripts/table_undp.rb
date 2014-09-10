require 'json'
require 'csv'

# Getting data from json
data = JSON.parse open('data/undp.json').read

def sp(a)
  return a.kind_of?(Array) ? a.join(' | ') : a 
end

# Generating the csv string
csv_string = CSV.generate do |csv|
  csv << [
    'identifier',
    'funding source',
    'theme',
    'geo location',
    'location',
    'level of intervention',
    'key collaborators',
    'hazards',
    'thematic',
    'agencies & organizations',
    'beneficiaries',
    'financing amount',
    'project status'
  ]

  for p in data
    d = p['data']

    if !d['nap'] && !d['p-cba']
      csv << [
        p['identifier'],
        p['funding'],
        p['theme'],
        p['location'],
        d['location'],
        sp(d['level-of-intervention']),
        sp(d['key-collaborators']),
        sp(d['climate-hazards']),
        d['thematic-area'],
        sp(d['partners']),
        d['beneficiaries'],
        d['financing-amount'],
        d['project-status']
      ]
    end
  end
end

File.open('undp.csv', 'w') {|f| f.write csv_string}
