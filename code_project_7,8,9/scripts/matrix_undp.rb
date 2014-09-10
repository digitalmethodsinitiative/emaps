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
    haystack.downcase.strip == needle
  else
    haystack.map {|e| e.downcase.strip}.include? needle
  end
end

# Getting data from json
data = JSON.parse(open('data/undp.json').read).select {|p| !p['data']['nap'] && !p['data']['p-cba']}

# Notes:
#  --> Tokenization issues
#  --> unspecified being null or not present
fields = [
  {
    :field => "theme",
    :values => [
      "natural resource management",
      "infrastructure/climate change risk management",
      "agriculture/food security",
      "water resources",
      "coastal zone development",
      "disaster risk reduction",
      "rural development",
      "health"
    ],
    :labels => [
      "natural resource mgmt",
      "infrastructure: cc risk mgmt",
      "agric: food security",
      "water resources",
      "coastal devt",
      "disaster risk reduction",
      "rural devt",
      "health"
    ]
  },
  {
    :field => "data/climate-hazards",
    :values => [
      "drought/water scarcity",
      "sea level rise",
      "disease",
      "extreme weather events",
      "flood",
      "land degradation and deforestation",
      "public health",
      "wildfire"
    ],
    :labels => [
      "drought, water scarcity",
      "sea level rise",
      "disease",
      "extreme weather events",
      "flood",
      "land degradation & deforestation",
      "public health",
      "wildfire"
    ],
    :field_label => "climate hazards"
  },
  
  
  
  
  
  
  {
    :field => "data/location",
    :values => [
      "urban",
      "rural"
    ],
    :field_label => "location type"
  },
  {
    :field => "data/level-of-intervention",
    :values => [
      "global",
      "national",
      "regional",
      "community",
      "district",
      "municipality"
    ],
    :field_label => "intervention level"
  },
  {
     :field => "location",
     :field_label => "country",
     :values => [
       "bolivia",
       "viet nam",
       "samoa",
       "kazakhstan",
       "niger",
       "bangladesh",
       "morocco",
       "jamaica",
       "guatemala",
       "bhutan",
       "burkina faso",
       "democratic republic of the congo",
       "lao people's democratic republic",
       "malawi",
       "solomon islands"
     ],
     :labels => [
       "Bolivia",
       "Vietnam",
       "Samoa",
       "Kazakhstan",
       "Niger",
       "Bangladesh",
       "Morocco",
       "Jamaica",
       "Guatemala",
       "Bhutan",
       "Burkina Faso",
       "Congo",
       "Laos",
       "Malawi",
       "Solomon Islands"
     ]
   }, 
  
  
  
  
  
  {
    :field => "data/project-status",
    :values => [
      "source of funds pipeline",
      "under implementation",
      "completed"
    ],
    :labels => [
      "funds src pipeline",
      "under impl",
      "completed"
    ],
    :field_label => "status"
  },
  {
    :field => "data/normalized_costs",
    :values => [
      lambda {|x| x < 240502},
      lambda {|x| x > 240502 && x <= 480000},
      lambda {|x| x > 480000 && x<= 3800000},
      lambda {|x| x > 3800000}
    ],
    :labels => [
      "< $240 502 (small)",
      "> $240 502 - <= $480 000 (small to medium)",
      "> $480 000 - <= $3 800 000 (medium to large)",
      "> $3 800 000 (large)"
    ],
    :field_label => "costs"
  },
  {
    :field => "data/funding-source",
    :values => [
      "gef-trust fund",
      "ldcf",
      "gef-spa",
      "bilateral finance",
      "sccf",
      "the adaptation fund",
      "decentralized cooperation"
    ],
    :labels => [
      "Gef-Trust Fund",
      "LDCF",
      "GEF-SPA",
      "bilateral finance",
      "SCCF",
      "Adaptation Fund",
      "decentralized coop"
    ],
    :field_label => "funding src"
  },
  
  
  
  
  
  {
    :field => "data/key-collaborators",
    :values => [
      "country office",
      "local governments",
      "national governments",
      "non-governmental organizations",
      "private sector partners",
      "unops"
    ],
    :labels => [
       "country office",
       "local gov",
       "national gov",
       "ngo",
       "pvt sector partners",
       "unops"
     ],
     :field_label => "key collaborators"
  },
  {
    :field => "data/partners",
    :values => [
      "undp",
      "gef",
      "the gef small grants programme",
      "un volunteers",
      "australian government",
      "unfccc secretariat",
      "adaptation fund",
      "european commission",
      "pacc",
      "sprep",
      "low emission capacity building programme (lecbp)",
      "government of switzerland",
      "government of japan",
      "united nations environment programme (unep)",
      "government of bangladesh",
      "world health organization (who)",
      "uk department for international development (dfid)"
    ],
    :labels => [
       "UNDP",
       "GEF",
       "GEF Small Grants Prog",
       "UN Volunteers",
       "Australian gov",
       "UNFCCC Secr",
       "Adaptation Fund",
       "EC",
       "PACC",
       "SPREP",
       "LECBP",
       "Switzerland gov",
       "Japan gov",
       "UNEP",
       "Bangladesh gov",
       "WHO",
       "DFID UK"
     ],
     :field_label => "partners"
  }
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
File.open('output/undp.dl', 'w') {|f| f.write gephi_export}

# Matrix headers
header_row = labels.unshift(nil)
matrix.unshift header_row

matrix.each_index do |i|
  if i > 0
    matrix[i].unshift header_row[i]
  end
end

CSV.open('output/undp_matrix.csv', 'w') do |csv|
  for m in matrix
    csv << m
  end
end
