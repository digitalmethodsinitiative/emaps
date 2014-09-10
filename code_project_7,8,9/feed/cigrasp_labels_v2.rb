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
      "meterological drought",
      "hydrological drought"
    ];
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
      "planned"
      "implementation running",
      "implemented",
    ],
    :labels => [
      "planned"
      "impl running",
      "implemented",
    ]
  },
  {
    :field => "project_costs/normalized_costs",
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
    ]
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