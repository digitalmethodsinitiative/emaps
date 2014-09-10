require 'json'
require 'csv'

# Examples
#-------------------------------------------------------------------------------
# EQUAL: project['key'] == 'Coastal Zone Development'
# NOT EQUAL: project['key'] != 'Coastal Zone Development'
# NESTED: project['key']['second_key'] == 'Coastal Zone Development'
# OR: condition1 || condition2
# AND: condition1 && condition2
# IN LIST?: project['key'].include?('value')
# NOT IN LIST?: !project['key'].include?('value')

def enforce(e)
  e ||= []
  e = [e] if !e.is_a?(Array)
  return e
end

# Queries
#-------------------------------------------------------------------------------
queries = {
  :undp_community => lambda do |p|
    d = p['data']

    (enforce(d['partners']).map(&:downcase).include?('The GEF Small Grants Programme'.downcase) ||
      enforce(d['partners']).map(&:downcase).include?('UN Volunteers'.downcase)) &&
    d['funding-source'].downcase == 'gef-spa' &&
    enforce(d['key-collaborators']).map(&:downcase).include?('non-governmental organizations') &&
    enforce(d['level-of-intervention']).map(&:downcase).include?('community') &&
    d['location'].downcase == 'rural' &&
    d['normalized_costs'] < 240502 &&
    p['theme'].downcase == 'agriculture/food security'
  end,

  :undp_national => lambda do |p|
    d = p['data']
    ch = enforce(d['climate-hazards'])
    kc = enforce(d['key-collaborators'])
    c = d['normalized_costs']

    (d['beneficiaries'] == 'Through improved identification of national circumstances, government agencies and other actors will increase their abilities to insulate at risk urban and rural populations from the adverse effects of climate change.' ||
      d['beneficiaries'] == 'Through improved capacity building and project identification, government agencies and other actors will increase their abilities to insulate at risk urban and rural populations from the adverse effects of climate change.') &&
    d['funding-source'].downcase == 'gef-trust fund' &&
    (ch.map(&:downcase).any_element_in?(['drought/water scarcity', 'land degradation and deforestation', 'sea level rise', 'extreme weather events', 'public health', 'disease'])) &&
    kc.include?('Country Office') &&
    enforce(d['level-of-intervention']).map(&:downcase).include?('national') &&
    d['location'].downcase == 'urban' &&
    d['project-status'].downcase == 'completed' &&
    (c > 240502 && c <= 480000) &&
    p['theme'] == 'Infrastructure/Climate Change Risk Management'
  end,

  :undp_regional => lambda do |p|
    d = p['data']
    d['funding-source'] ||= ''
    
    ['ldcf', 'sccf', 'the adaptation fund'].include?(d['funding-source'].downcase)  &&
    enforce(d['key-collaborators']).map(&:downcase).include?('local governments') &&
    enforce(d['level-of-intervention']).map(&:downcase).any_element_in?(['district', 'regional', 'municipality']) &&
    d['normalized_costs'] > 480000
  end,

  :cigrasp_community => lambda do |p|
    c = p['project_costs']['normalized_costs']
    pc = p['project_classification']
    ps = p['problem_solving_capacity_an_reversibility']
    o = p['overview']

    (c && p['project_costs']['normalized_costs'] < 116000) &&
    pc['effect_persistence'] == 'between 1 and 10 years' &&
    pc['effect_emergence'] == 'immediate' &&
    # ps['reversibility'] == 'low' &&
    # o['impacts'].include?('Agricultural production loss') &&
    p['scale'] == 'local'
  end,

  :cigrasp_national => lambda do |p|
    c = p['project_costs']['normalized_costs']
    pc = p['project_classification']
    ps = p['problem_solving_capacity_an_reversibility']
    o = p['overview']

    (c && (c > 116000 && c <= 10515000)) &&
    pc['effect_persistence'] == 'more than 10 years' &&
    pc['effect_emergence'] == 'not immediate' &&
    ['regional', 'national'].include?(p['scale'])
  end
}
#-------------------------------------------------------------------------------

# DONT GO THIS WAY, TOMMASO!
# STAY UP
#-------------------------------------------------------------------------------
# Monkey patching
class Array
  def any_element_in?(a)
    (self - a).length < self.length
  end
end

# Arguments
if ARGV[0] == 'help' || ARGV[0] == '-h' || ARGV[0] == '--help'
  puts 'Usage: ruby query.rb <query-name> <path-to-file> [<path-to-output>]'
  exit
end

if ARGV.length < 2
  puts 'Wrong arguments.'
  exit
end

query = ARGV[0].to_sym
db = JSON.parse(open(ARGV[1]).read)
output = ARGV[2] || query.to_s + '.csv'

if !queries.include? query
  puts 'Inexistant query.'
  exit
else
  query = queries[query]
end

# Filtering
projects = db.select &query

puts "#{projects.length} projects found."

# Writing to csv file
@exceptions = [
  'data',
  'overview',
  'project_classification',
  'project_costs',
  'problem_solving_capacity_an_reversibility',
  'responsibilities',
  'evaluative_information'
]

def makeheaders(p)
  a = []
  p.each do |k, v|
    if !@exceptions.include? k
      a << k
    else
      v.each do |k2, v2|
        a << k + '/' + k2
      end
    end
  end
  a
end

@headers = makeheaders(db[200])

def flatcsv(p)
  a = []
  p.each do |k, v|
    if !@exceptions.include? k
      a[@headers.index(k)] = (v.is_a?(Array) ? v.join(' | ') : v)
    else
      v.each do |k2, v2|
        a[@headers.index(k + '/' + k2)] = (v2.is_a?(Array) ? v2.join(' | ') : v2)
      end
    end
  end
  a
end

CSV.open(output, 'w') do |csv|
  csv << @headers
  for p in projects
    csv << flatcsv(p)
  end
end
#-------------------------------------------------------------------------------
