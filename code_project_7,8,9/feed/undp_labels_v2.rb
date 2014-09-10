example = [
  {
    :field => "DONT TOUCH",
    :values => [
      "infrastructure/climate change risk management",
      "agriculture/food security",
      "water resources",
      "natural resource management",
      "coastal zone development",
      "disaster risk reduction",
      "rural development",
      "health"
    ],
    :labels => [
      "infrastructure/climate change risk management",
      "agriculture/food security",
      "OTHER MODIFIED LABEL",
      "natural resource management",
      "coastal zone development",
      "disaster risk reduction",
      "rural development",
      "MODIFIED LABEL"
    ]
  }
]


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
      "urban"
      "rural",
    ],
    :field_label => "location type"
  },
  {
    :field => "data/level-of-intervention",
    :values => [
      "global",
      "national",
      "regional"
      "community",
      "district",
      "municipality",
    ],
    :field_label => "intervention level"
  },
  {
     :field => "geo location",
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
       "congo",
       "laos",
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
      "source of funds pipeline"
      "under implementation",
      "completed",
    ],
    :labels => [
      "funds src pipeline"
      "under impl",
      "completed",
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
