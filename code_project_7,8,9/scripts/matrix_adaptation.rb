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
data = JSON.parse(open('data/adaptation_projects.json').read)

# Notes:
#  --> Tokenization issues
#  --> unspecified being null or not present
fields = [
  {
    :field => 'countries',
    :values => [
      'Viet Nam',
      'Indonesia',
      'Bangladesh',
      'Kenya',
      'Philippines',
      'Cambodia',
      'Thailand',
      'Egypt',
      'Ethiopia',
      'Pakistan',
      'India',
      'China',
      'Turkey',
      'Tanzania',
      'Brazil',
      'Morocco',
      'Samoa',
      'Kazakhstan',
      'Jamaica',
      'Bolivia',
      'United States of America',
      'Niger',
      'Mexico'
    ]
  },
  {
    :field => 'source',
    :values => [
      'undp',
      'psi',
      'climatewise'
    ]
  },
  {
    :field => 'themes',
    :values => [
      'Agriculture/Food Security',
      'Coastal Zone Development',
      'Disaster Risk Reduction',
      'Health',
      'Infrastructure/Climate Change Risk Management',
      'Natural Resource Management',
      'Rural Development',
      'Water Resources'
    ]
  },
  {
    :field => 'key-collaborators',
    :values => [
      'Country Office',
      'Local Governments',
      'National Governments',
      'Non-Governmental Organisations',
      'Non-Governmental Organizations',
      'Private Sector Partners',
      'UNOPS'
    ]
  },
  {
    :field => 'climate-hazards',
    :values => [
      'Disease',
      'Drought',
      'Drought/Water Scarcity',
      'Earthquake',
      'El Nino',
      'Extreme Weather Events',
      'Flood',
      'Land Degradation and Deforestation',
      'Public Health',
      'Sea Level Rise',
      'Wildfire'
    ]
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
File.open('output/adaptation.dl', 'w') {|f| f.write gephi_export}


# Matrix headers
header_row = labels.unshift(nil)
matrix.unshift header_row

matrix.each_index do |i|
  if i > 0
    matrix[i].unshift header_row[i]
  end
end

CSV.open('output/adaptation_matrix.csv', 'w') do |csv|
  for m in matrix
    csv << m
  end
end
