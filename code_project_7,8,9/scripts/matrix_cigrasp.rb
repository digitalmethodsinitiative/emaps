require 'json'
require 'csv'
require 'matrix'
require 'fileutils'

# Monkey patching
class Hash
  alias :__fetch :[]

  def traverse(path, fallback=nil)
    return path.inject(self) { |obj, item| obj.__fetch(item) || break } || fallback
  end
end

class String
  def pad(nb)
    (self + ' ' * nb)[0..nb]
  end
end

# Helpers
def compare(haystack, needle)
  if haystack == nil
    false
  elsif needle.is_a? Proc
      needle.call(haystack)
  elsif !haystack.kind_of? Array
    haystack.downcase.strip == needle.downcase.strip
  else
    haystack.map {|e| e.downcase.strip}.include? needle.downcase.strip
  end
end

# Getting data from json
data = JSON.parse(open('data/cigrasp.json').read)

# Notes:
#  --> Tokenization issues
#  --> unspecified being null or not present
fields = [
  {
    :field => "types",
    :values => [
      "natural resource management",
      "assessment report",
      "training",
      "building / installing structure",
      "technical advice",
      "communication",
      "coordination",
      "regulation / law",
      "incentive structure",
      "agricultural management"
    ],
    :field_label => "types (theme)",
    :labels => [
      "natural resource mgmt",
      "assessment report",
      "training",
      "building / installing struct",
      "tech advice",
      "comm",
      "coord",
      "reg / law",
      "incentive struct",
      "agricultural mgmt"
    ],
  },
  {
    :field => "overview/stimuli",
    :values => [
      "drought",
      "sea-level rise",
      "precipitation change",
      "temperature change",
      "meterological drought",
      "hydrological drought"
    ],
    :field_label => "stimuli (climate hazards)",
    :labels => [
      "drought",
      "sea level rise",
      "precipitation change",
      "temperature change",
      "meteorological drought",
      "hydrological drought"
    ]
  },
  
  
  
  
  
  
  {
    :field => "overview/impacts",
    :values => [
      "land loss",
      "water stock reduction",
      "soil moisture reduction",
      "agricultural production loss",
      "rainfed agricultural production loss",
      "wetland loss",
      "food loss",
      "increased forest fire frequency",
      "migration",
      "irrigated agricultural production loss",
      "land-cover conversion",
      "rural and urban area damages",
      "other impacts",
      "livestock production decrease",
      "urban water supply decrease",
      "agricultural gdp loss",
      "relocation"
    ],
    :field_label => "impacts",
    :labels => [
      "land loss",
      "water stock red",
      "soil moisture red",
      "agric prod loss",
      "rainfed agric prod loss",
      "wetland loss",
      "food loss",
      "increased forest fire freq",
      "migration",
      "irrigated agric prod loss",
      "land-cover conversion",
      "rural and urban area damages",
      "other",
      "livestock prod decrease",
      "urban water supply decrease",
      "agric GDP loss",
      "relocation"
    ]
  },
  
  
  
  
  
  
  {
    :field => "overview/sector",
    :field_label => "sector (location type)",
    :values => [
      "city",
      "agriculture",
      "coast",
      "forestry",
      "water"
    ],
    :labels => [
      "city",
      "agriculture",
      "coast",
      "forestry",
      "water"
    ]  },
  {
    :field => "scale",
    :values => [
      "global",
      "national",
      "regional",
      "local",
      "transboundary"
    ],
    :field_label => "scale (intervention level)",
    :labels => [
      "global",
      "national",
      "regional",
      "local",
      "transboundary"
    ]
  },
  
  
  
  
  
  {
    :field => "continent",
    :values => [
      "Africa",
      "Asia",
      "Australia / Oceania",
      "Europe",
      "North America",
      "South America"
    ]
  },
  {
    :field => "country",
    :field_label => "country",
    :values => [
      "Bolivia, Plurinational State of",
      "Viet Nam",
      "India",
      "South Africa",
      "Peru",
      "Tunisia",
      "China",
      "Brazil",
      "Indonesia",
      "Philippines"
    ],
    :labels => [
      "Bolivia",
      "Vietnam",
      "India",
      "South Africa",
      "Peru",
      "Tunisia",
      "China",
      "Brazil",
      "Indonesia",
      "Philippines"
    ]
  },
  
  
  
  
  
  
  
  {
    :field => "project_classification/project_status",
    :field_label => "status (status)",
    :values => [
      "planned",
      "implementation running",
      "implemented"
    ],
    :labels => [
      "planned",
      "impl running",
      "implemented"
    ]
  },
  {
    :field => "project_costs/normalized_costs",
    :field_label => "costs",
    :values => [
      lambda {|x| x < 116000},
      lambda {|x| x > 116000 && x <= 2000000},
      lambda {|x| x > 2000000 && x<= 10515000},
      lambda {|x| x > 10515000}
    ],
    :labels => [
      "< $116 000 (very small)",
      "> $116 000 - <= $2 000 000 (medium)",
      "> $2 000 000 - <= $10 515 000 (medium to very large)",
      "> $10 515 000 (very large)"
    ]
  },
  
  
  
  
  
  
  
  {
    :field => "project_classification/running_time",
    :field_label => "running time",
    :values => [
      "1 years",
      "18 months",
      "2 years",
      "3 years",
      "4 years",
      "5 years",
      "6 years",
      "10 years"
    ],
    :labels => [
      "1 years",
      "1.5 years",
      "2 years",
      "3 years",
      "4 years",
      "5 years",
      "6 years",
      "10 years"
    ]
  },
  {
    :field => "project_classification/effect_emergence",
    :field_label => "effect emergence",
    :values => [
      "immediate",
      "not immediate"
    ]
  },
  {
    :field => "project_classification/effect_persistence",
    :field_label => "effect persistence",
    :values => [
      "between 1 and 10 years",
      "more than 10 years",
      "not specified",
      "up to 1 year"
    ],
    :labels => [
      "1 to 10 years",
      "more than 10 years",
      "not specified",
      "up to 1 year"
    ]
  },
  {
    :field => "problem_solving_capacity_an_reversibility/problem_solving_coverage",
    :field_label => "problem solving coverage",
    :values => [
      "high",
      "low",
      "medium"
    ]
  },
  {
    :field => "problem_solving_capacity_an_reversibility/reversibility",
    :field_label => "reversibility",
    :values => [
      "high",
      "medium",
      "low"
    ]
  },


]

def get_idx(f, i)
  f[:field] + ' | ' + (f[:labels] || f[:values])[i]
end

def get_label(f, i)
  (f[:field_label] || f[:field]) + ' | ' + (f[:labels] || f[:values])[i]
end

values = fields.map {|f| f[:values]}.flatten
matrix = values.map {|i| Array.new values.length}
labels = fields.map {|f| f[:values].each_index.map {|i| get_label(f, i)}}.flatten
idx = fields.map{|f| f[:values].each_index.map {|i| get_idx(f, i)}}.flatten

# Iterate through fields
for f in fields

  # Iterate through field values
  f[:values].each_with_index do |v, vi|

    # Filtering the needed projects
    projects = data.select {|p| compare(p.traverse(f[:field].split('/'), f[:nil_value]), v)}

    # Looping through every other criteria to build the matrix
    for f2 in fields

      # Other values
      f2[:values].each_with_index do |v2, v2i|
        matrix[idx.index(get_idx(f, vi))][idx.index(get_idx(f2, v2i))] = \
          projects.select {|p| compare(p.traverse(f2[:field].split('/'), f2[:nil_value]), v2)}.length
      end
    end
  end
end

p = 3

# Debug display
# for row in matrix
#   p row.map {|e| e.to_s.pad(p)}
# end

puts Matrix.rows(matrix).square?

gephi_export = %Q[
dl n=#{matrix.length}
format = fullmatrix
labels:
#{labels.map {|e| e.gsub(/,/, ';')}.join(',')}
data:
]

for row in matrix
  gephi_export += row.join(' ') + "\n"
end

FileUtils.mkdir_p 'output'
File.open('output/cigrasp.dl', 'w') {|f| f.write gephi_export}


# Matrix headers
header_row = labels.unshift(nil)
matrix.unshift header_row

matrix.each_index do |i|
  if i > 0
    matrix[i].unshift header_row[i]
  end
end

CSV.open('output/cigrasp_matrix.csv', 'w') do |csv|
  for m in matrix
    csv << m
  end
end
