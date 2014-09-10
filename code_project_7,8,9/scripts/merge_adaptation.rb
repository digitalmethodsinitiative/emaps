require 'json'
require 'csv'

# Désenclaver les blocs en ruby

@db = []
@thesaurus = {
  :countries => []
}
@headers = [
  'source',
  'name',
  'country',
  'climate-hazards',
  'key-collaborators',
  'theme'
]

def enforce_array(e)
  if e && e != ''
    e.is_a?(Array) ? e : [e]
  else
    []
  end
end

undp = JSON.parse(open('data/undp.json').read).select \
  {|p| !p['data']['nap'] && !p['data']['p-cba']}

climatewise = []

# UNDP
for p in undp
  d = p['data']
  ch = enforce_array(d['climate-hazards'])

  if d['summary'] =~ /earthquake/i
    ch << 'Earthquake'
  end

  @db << {
    'source' => 'undp',
    'name' => p['title'].strip,
    'countries' => enforce_array(p['location']),
    'climate-hazards' => ch,
    'key-collaborators' => enforce_array(d['key-collaborators']),
    'themes' => enforce_array(p['theme'].strip)
  }

  if !@thesaurus[:countries].include?(p['location']) && p['location'] != ''
    @thesaurus[:countries] << p['location']
  end
end

# PSI
unknown_countries = []
unknown_themes = []
psi_matching = {
  :themes => {
    'Agriculture, forestry and fisheries' => 'Agriculture/Food Security',
    'Food' => 'Agriculture/Food Security',
    'Food security' => 'Agriculture/Food Security',
    'Capacity building' => 'Disaster Risk Reduction',
    'Education and training' => 'Disaster Risk Reduction',
    'Capacity building, education and training' => 'Disaster Risk Reduction',
    'Science, assessment, monitoring and early warning' => 'Disaster Risk Reduction',
    'Technology and Information & Communications Technology (ICT)' => 'Infrastructure/Climate Change Risk Management',
    'Construction and Engineering' => 'Infrastructure/Climate Change Risk Management',
    'Energy and Utilities' => 'Infrastructure/Climate Change Risk Management',
    'Transport, infrastructure and human settlements' => 'Infrastructure/Climate Change Risk Management',
    'Infrastructure and human settlements' => 'Infrastructure/Climate Change Risk Management',
    'Finance and insurance' => 'Infrastructure/Climate Change Risk Management',
    'Human health' => 'Health',
    'Oceans and coastal areas' => 'Coastal Zone Development',
    'Renewable energy systems' => 'Natural Resource Management',
    'Terrestrial ecosystems' => 'Rural Development',
    'Water resources' => 'Water Resources'
  }
}

CSV.foreach('feed/psi.csv', :headers => :first_row, :col_sep=> "\t") do |row|
  row[4].gsub!(/\s/, ' ')

  for c in row[4].split('; ')
    if !@thesaurus[:countries].include? c.strip
      if !unknown_countries.index(c.strip)
        unknown_countries << c.strip
      end
    end
  end

  themes = row[3].strip.split('; ').map do |t|
    t.strip!

    if !psi_matching[:themes][t]
      unknown_themes << t
    end
    psi_matching[:themes][t]
  end

  @db << {
    'source' => 'psi',
    'name' => row[0].strip,
    'countries' => row[4].split('; ').map {|c| c.strip},
    'climate-hazards' => [],
    'key-collaborators' => [],
    'themes' => themes.uniq.select {|t| t != nil}
  }
end

CSV.foreach('feed/climatewise.csv', :headers => :first_row, :col_sep=> "\t") do |row|

  @db << {
    'source' => 'climatewise',
    'name' => row[0].strip,
    'countries' => row[4].strip.split('|').map(&:strip).select {|c| c != 'non specified'},
    'climate-hazards' => row[1] ? row[1].split('|').map(&:strip) : [],
    'key-collaborators' => row[2] ? row[2].split('|').map(&:strip) : [],
    'themes' => row[3] ? row[3].split('|').map(&:strip) : []
  }
end

File.open('output/adaptation_projects.json', 'w') {|f| f.write(JSON.pretty_generate(@db))}
