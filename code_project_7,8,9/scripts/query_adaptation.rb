require 'json'
require 'csv'
require 'fileutils'

# Output
o = 'output/queries_results'
FileUtils.mkdir_p o

# Data
data = JSON.parse(open('data/adaptation_projects.json').read)
@table = JSON.parse(open('feed/country_table.json').read)
@data = {
  :undp => data.select {|p| p['source'] == 'undp'},
  :psi => data.select {|p| p['source'] == 'psi'},
  :climatewise => data.select {|p| p['source'] == 'climatewise'}
}

# # Globals
@dbs = [:undp, :psi, :climatewise]
@themes = [
  'Agriculture/Food Security',
  'Coastal Zone Development',
  'Disaster Risk Reduction',
  'Health',
  'Infrastructure/Climate Change Risk Management',
  'Natural Resource Management',
  'Rural Development',
  'Water Resources'
]
@countries = data.map {|p| p['countries']}.flatten.uniq.sort[1..-1]
@key_collaborators = data.map {|p| p['key-collaborators']}.flatten.uniq.sort
@climate_hazards = data.map {|p| p['climate-hazards']}.flatten.uniq.sort

# count = []
# for c in @countries
#   count << {
#     :nb => data.select {|p| p['countries'].include? c}.length,
#     :country => c
#   }
# end

# count.sort_by! {|c| -c[:nb]}

# for c in count
#   p c
# end

# exit

#-------------------------------------------------------------------------------
# 1) Nb of projects per source and per theme
#        Databases
# Themes    nb
themes_per_dbs = {}
for db in @dbs
  themes_per_dbs[db] = {}

  for p in @data[db]
    for t in p['themes']
      themes_per_dbs[db][t] ||= 0
      themes_per_dbs[db][t] += 1
    end
  end
end

CSV.open(o + '/themes_per_dbs.csv', 'w') do |csv|
  csv << [nil] + @dbs.map(&:to_s)

  for t in @themes
    csv << [t] + @dbs.map {|d| themes_per_dbs[d][t] || 0}
  end
end
#-------------------------------------------------------------------------------

#-------------------------------------------------------------------------------
# 1) Nb of projects per countries and per theme
#        countries
# themes    nb
for db in @dbs
  csv_array = []
  csv_array << ['country', 'theme', 'count']

  for t in @themes
    for c in @countries
      csv_array << [
        @table[c] || c,
        t,
        @data[db].select {|p| p['countries'].include?(c) && p['themes'].include?(t)}.length
      ]
    end
  end

  csv_array.select! {|r| r[2] != 0}
  # csv_array.sort_by! {|r| r[2]}

  CSV.open(o + '/' + db.to_s + '_themes_per_countries.csv', 'w') do |csv|
    for r in csv_array
      csv << r
    end
  end
end
#-------------------------------------------------------------------------------

CSV.open(o + '/climate-hazards_per_dbs.csv', 'w') do |csv|
  csv << ['db', 'climate-hazard', 'count']
  for db in @dbs - [:psi]
    for c in @climate_hazards
      csv << [
        db.to_s,
        c,
        @data[db].select {|p| p['climate-hazards'].include? c}.length
      ]
    end
  end
end

CSV.open(o + '/drought_per_dbs_per_country.csv', 'w') do |csv|
  csv << ['db', 'country', 'count']
  for db in @dbs - [:psi]
    for c in @countries
      csv << [
        db.to_s,
        c,
        @data[db].select {|p| p['countries'].include?(c) && p['climate-hazards'].include?('Drought/Water Scarcity')}.length
      ]
    end
  end
end

# bengali square