require 'json'
require 'yaml'

# Monkey patching
class Hash
  alias :__fetch :[]

  def traverse(path, fallback=nil)
    return path.inject(self) { |obj, item| obj.__fetch(item) || break } || fallback
  end
end


# Getting data from json
data = JSON.parse open('data/undp.json').read
thesaurus = {}
fields_to_thesaurize = [
  {:field => 'location', :label => 'geolocation'},
  {:field => 'data/climate-hazards', :label => 'climate_hazards'},
  {:field => 'data/key-collaborators', :label => 'key_collaborators'},
  {:field => 'theme', :label => 'theme'}
]

for p in data
  for f in fields_to_thesaurize
    v = p.traverse(f[:field].split('/'))
    thesaurus[f[:label]] ||= []

    if v.is_a? Array
      for e in v
        if !thesaurus[f[:label]].index(e) && e != '' && e
          thesaurus[f[:label]] << e
        end
      end
    else
      if !thesaurus[f[:label]].index(v) && v != '' && v
        thesaurus[f[:label]] << v
      end
    end
  end
end

for k, v in thesaurus
  v.sort_by! {|i| i}
end

File.open('output/undp_thesaurus.yml', 'w') {|f| f.write(thesaurus.to_yaml(:indent => 2, :useHeader => false))}
