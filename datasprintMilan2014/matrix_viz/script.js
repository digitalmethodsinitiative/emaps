/*
 * Author Erik Borra <erik@digitalmethods.net>
 * Based on http://bost.ocks.org/mike/miserables/
 */

// initialize
var dataset = "substance_of_adaptation.json";
var orderby = "count"; // count or alphabet
var filter = ""; // only for adaptation_projects.json
d3.selectAll('.radio').on('change', function(){
    orderby = this.value;
});
d3.select("#select1").style("display","none");
d3.select("#select2").style("display","none");

// here the possible selections are defined
d3.select("#dataset").on("change",function(){
    // @todo, write title of dataset
    if(this.value == "substance_of_adaptation") {
        dataset = "substance_of_adaptation.json";
        d3.select("#select1").style("display","none");
        d3.select("#select2").style("display","none");
    } else {
        var fields = [],
        fieldNames = "";
        if(this.value == "undp") {
            dataset = "adaptation_projects.json"; 
            fields = ["themes","countries","climate-hazards","key-collaborators"];
            fieldNames = ["sectors","countries","climate hazards","key collaborators"];
            fillOptions("#field1",fields,fieldNames,0);
            fillOptions("#field2",fields,fieldNames,1);
            filter = "undp";
        } else if(this.value == "psi") {
            dataset = "adaptation_projects.json"; 
            fields = ["themes","countries","climate-hazards","key-collaborators"];
            fieldNames = ["sectors","countries","climate hazards","key collaborators"];
            fillOptions("#field1",fields,fieldNames,0);
            fillOptions("#field2",fields,fieldNames,1);
            filter = "psi";
        } else if(this.value == "climatewise") {
            dataset = "adaptation_projects.json"; 
            fields = ["themes","countries","climate-hazards","key-collaborators"];
            fieldNames = ["sectors","countries","climate hazards","key collaborators"];
            fillOptions("#field1",fields,fieldNames,0);
            fillOptions("#field2",fields,fieldNames,1);
            filter = "climatewise";
        } else if(this.value == "cigrasp") {
            dataset = "cigrasp.json";
            fields = ["overview.sector","country","types","scale","overview.stimuli","overview.impacts","project_classification.project_type","project_classification.project_status","project_classification.running_time","project_classification.spatial_scale","project_classification.effect_emergence","project_classification.effect_persistence","problem_solving_capacity_an_reversibility.problem_solving_coverage","problem_solving_capacity_an_reversibility.reversibility","responsibilities.initiating_agent","responsibilities.executing_agent","responsibilities.funding_source"];
            fieldNames = ["sectors","countries","types","scale","stimuli","impacts","project type","project status","running time","spatial scale","effect emergence","effect persistence","problem solving coverage","reversibility","initiating agent","executing agent","funding source"];
            fillOptions("#field1",fields,fieldNames,0);
            fillOptions("#field2",fields,fieldNames,1);
        } else if(this.value == "oecd") {
            dataset = "oecd.json";
            fields = ["SectorNameE", "recipientnameE","donornameE", "agencynameE", "purposename_e", "RegionNameE", "IncomeGroupNameE"];
            fieldNames = ["sectors","recipient countries","donor countries","agency","purposes","regions","income Groups"];
            fillOptions("#field1",fields,fieldNames,0);
            fillOptions("#field2",fields,fieldNames,1);
        } else if(this.value == "climatefundsupdate") {
            dataset = "climatefundsupdate.json";
            fields = ["category", "keywords", "Focus", "Financial Instrument", "Country", "Country Income Level", "Region", "Funder", "Implementor"];
            fieldNames = ["category", "keywords", "Focus", "Financial Instrument", "Country", "Country Income Level", "Region", "Funder", "Implementor"];
            fillOptions("#field1",fields,fieldNames,0);
            fillOptions("#field2",fields,fieldNames,1);
        } else if(this.value == "") {
            dataset = "napa.json";
            fields = ["Category","Country", "Keywords"];
            fieldNames = ["Category","Country", "Keywords"];
            fillOptions("#field1",fields,fieldNames,0);
            fillOptions("#field2",fields,fieldNames,1);
        }
        
        d3.select("#select1").style("display","block");
        d3.select("#select2").style("display","block");
    }    
});
d3.select("#form").on('submit',function() {
    d3.event.preventDefault();
    d3.event.stopPropagation();
    updateChart();
});

//updateChart(); // @todo, enable

function updateChart() {
    
    var source = "",
    target = "";
    
    if(dataset == "substance_of_adaptation.json") {
        source = "source"; // @todo, can also be something else!!
        target = "recipient_mapped";
    } else {
        source = d3.select('#field1').node().value;
        target = d3.select('#field2').node().value;
    }
    
    drawChart(orderby,dataset,source,target,filter);
}

function fillOptions(fieldid, fields, fieldNames,index) {
    d3.select(fieldid).selectAll("option").remove();
    
    d3.select(fieldid).selectAll("option")
    .data(d3.values(fields))
    .enter()
    .append("option")
    .attr("value", function(d,i){
        return d;
    })
    .text(function(d,i){
        return fieldNames[i];
    });
}

function drawChart(orderby,dataset,source,target,filter) {
    //console.log(orderby + " " + dataset + " " + source + " " + target);
    d3.select("svg").remove();
    var margin = {
        top: 140, 
        right: 140, 
        bottom: 10, 
        left: 140
    },
    width = 720,
    height = 2000;

    var x = d3.scale.ordinal().rangeBands([0, width]);

    var svg = d3.select("body").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
    .style("margin-left", -margin.left + "px")
    .append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    d3.json("data/"+dataset, function(datasets) {
        var nodes = [],
        links = [],
        sources = [],
        targets = [];
        
        datasets.forEach(function(d,i) {
            
            if(filter != "" && d.source != filter)
                return;
            
            var dsources = eval("d."+source);
            var dtargets = eval("d."+target);
            if(dsources == "" || dtargets == "")
                return;
            
            if(dsources.constructor != Array)
                dsources = [dsources];
            if(dtargets.constructor != Array)
                dtargets = [dtargets];
                
            dsources.forEach(function(s) { 
                if(s != 'Non-specific') {
                    var sid = nodeIndex(s,sources);
                    if(sid < 0) {
                        sources.push({
                            "name":s
                        });
                        sid = nodeIndex(s,sources);
                    }
                        
                    dtargets.forEach(function(t) { 
                        if(t != 'Non-specific') {
                            var tid = nodeIndex(t,targets);
                            if(tid < 0) {
                                targets.push({
                                    "name":t
                                });
                                tid = nodeIndex(t,targets);
                            }
                
                            var li = linkIndex(sid, tid, links);
                            if(li < 0)
                                links.push({
                                    "source":sid,
                                    "target":tid,
                                    "value":1
                                });
                            else
                                links[li].value++;
                        }
                    });
                }
            }); 
        });
        
    
        var matrix = [],
        ntargets = targets.length,
        nsources = sources.length;

        // Compute index per node
        targets.forEach(function(target, i) {
            target.count = 0;
            matrix[i] = d3.range(nsources).map(function(j) {
                sources[j].count = 0;
                return {
                    x: j, 
                    y: i, 
                    z: 0
                };        
            });
        });
    
        // Convert links to matrix; count character occurrences.
        var max = 0;
        links.forEach(function(link) {
            matrix[link.target][link.source].z += link.value;
            if(max<link.value)
                max = link.value;
            targets[link.target].count += link.value;
            sources[link.source].count += link.value;
        });
        
        var y = d3.scale.ordinal().rangeBands([0, 10*ntargets]);
        var z = d3.scale.linear().domain([0, max]);
        
        // Precompute the orders.
        var sourceOrders = {
            name: d3.range(nsources).sort(function(a, b) {
                return d3.ascending(sources[a].name, sources[b].name);
            }),
            count: d3.range(nsources).sort(function(a, b) {
                return d3.descending(sources[a].count, sources[b].count);
            })
        };
        var targetOrders = {
            name: d3.range(ntargets).sort(function(a, b) {
                return d3.ascending(targets[a].name, targets[b].name);
            }),
            count: d3.range(ntargets).sort(function(a, b) {
                return d3.descending(targets[a].count, targets[b].count);
            })
        };

        // The default sort order.
        if(orderby == "count") {
            x.domain(sourceOrders.count);
            y.domain(targetOrders.count);
        } else {
            x.domain(sourceOrders.name);
            y.domain(targetOrders.name);
        }
    
     
        var row = svg.selectAll(".row")
        .data(matrix)
        .enter().append("g")
        .attr("class", "row")
        .attr("transform", function(d, i) {
            return "translate(0," + y(i)/2 + ")";
        })
        .each(row);

        //row.append("line")
        //.attr("x2", width);

        row.append("text")
        .attr("x", -6)
        .attr("y", 2.5) //y.rangeBand()/2)
        .attr("dy", ".12em")
        .attr("transform", function(d, i) {
            return "translate(0," + (y(i)+2)/2 + ")";
        })
        .attr("text-anchor", "end")
        .text(function(d, i) {
            return targets[i].name;
        });

        var column = svg.selectAll(".column")
        .data(sources)
        .enter().append("g")
        .attr("class", "column")
        .attr("transform", function(d, i) {
            return "translate(" + x(i) + ",-10)rotate(-30)";
        });

        column.append("text")
        .attr("x", 6)
        .attr("y", 2.5) //y.rangeBand() / 2)
        .attr("dy", ".32em")
        .attr("text-anchor", "start")
        .text(function(d, i) {
            return d.name;
        });

        function nodeIndex(name, list) {
            var i;
            for(i=0;i<list.length;i++) {
                if(list[i].name == name)
                    return i;
            }
            return -1;
        }
    
        function linkIndex(source, target, list) {
            var i;
            for(i=0;i<list.length;i++) {
                if(list[i].source == source && list[i].target == target)
                    return i;
            }
            return -1;
        }

        function row(row) {
            var cell = d3.select(this).selectAll(".cell")
            .data(row.filter(function(d) {
                return d.z;
            }))
            .enter().append("rect")
            .attr("class", "cell")
            .attr("x", function(d) {
                return x(d.x);
            })
            .attr("y", function(d) {
                return y(d.y)/2;
            })
            .attr("width", function(d) {
                return z(d.z) * (x.rangeBand()-5)
            })
            .attr("height", 5) //y.rangeBand()/2)
            .style("fill", function(d,i) {
                return "rgb(31, 119, 180)";
            })
            .on("mouseover", mouseover)
            .on("mouseout", mouseout)
            .append("svg:title")
            .text(function(d) {
                return d.z + " * (" + sources[d.x].name + " + "+targets[d.y].name+")";
            });
        }

        function mouseover(p) {
            d3.selectAll(".row text").classed("active", function(d, i) {
                return i == p.y;
            });
            d3.selectAll(".column text").classed("active", function(d, i) {
                return i == p.x;
            });
        }

        function mouseout() {
            d3.selectAll("text").classed("active", false);
        }

    });
}
