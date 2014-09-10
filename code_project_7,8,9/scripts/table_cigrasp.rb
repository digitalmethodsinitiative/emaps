require 'json'
require 'csv'

# Getting data from json
data = JSON.parse open('data/cigrasp.json').read

def sp(a)
  return a.kind_of?(Array) ? a.join(' | ') : a 
end

# Generating the csv string
csv_string = CSV.generate do |csv|
  csv << [
    'identifier',
    'list/continent',
    'list/country',
    'list/type',
    'list/scale',
    'project page/overview/sector',
    'project page/overview/stimulus',
    'project page/overview/impacts',
    'project page/project_classification/project_status',
    'project page/project_classification/running_time',
    'project page/project_classification/effect_emergence',
    'project page/project_classification/effect_persistence',
    'project page/project_costs/total_costs',
    'project page/problem_solving_capacity_and reversibility/problem_solving_coverage',
    'project page/problem_solving capacity_and_reversibility/reversibility',
    'project page/responsibilities/initiating_agent',
    'project page/responsibilities/executing_agent',
    'project page/responsibilities/funding_source',
    'project page/evaluative_information/success_factors',
    'project page/evaluative_information/limiting_factors',
    'project page/evaluative_information/synergies_to_mitigation',
    'project page/evaluative_information/no_regret_win-win_option',
    'project page/evaluative_information/project_evaluation'
  ]

  for p in data
    o = p['overview']
    c = p['project_classification']
    s = p['problem_solving_capacity_an_reversibility']
    r = p['responsibilities']
    e = p['evaluative_information']

    csv << [
      p['identifier'],
      p['continent'],
      p['country'],
      sp(p['types']),
      p['scale'],
      o['sector'],
      sp(o['stimuli']),
      sp(o['impacts']),
      c['project_status'],
      c['running_time'],
      c['effect_emergence'],
      c['effect_persistence'],
      p['project_costs']['total_costs'],
      s['problem_solving_coverage'],
      s['reversibility'],
      r['initiating_agent'],
      r['executing_agent'],
      r['funding_source'],
      e['success_factors'],
      e['limiting_factors'],
      e['synergies_to_mitigation'],
      e['no_regret_/_win-win_option'],
      e['project_evaluation']
    ]
  end
end

File.open('cigrasp.csv', 'w') {|f| f.write csv_string}
