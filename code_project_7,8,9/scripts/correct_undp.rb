require 'json'
require 'csv'

# Monkey patching
class Array
  def some(&block)
    e = index &block
    e ? at(e) : nil
  end
end

# Getting data from json
data = JSON.parse open('data/undp.json').read

CSV.foreach('feed/undp_corrected_costs.csv', :headers => :first_row) do |row|
  target = data.some {|p| p['identifier'] == row[0]}
  target['data']['normalized_costs'] = row[1] == 'not specified' ? nil : row[1].to_i
end

File.open('undp.json', 'w') {|f| f.write(JSON.pretty_generate(data))}
