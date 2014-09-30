/*
 * Author Erik Borra <erik@digitalmethods.net>
 * Based on http://bost.ocks.org/mike/miserables/
 */

var dataset = "",
filter = "",
orderby = "count", // count or alphabet
whichdata = 'all'; // all or bangladeshindia

dataset = "undp.json";
fields = ["theme","location","data.level-of-intervention","data.key-collaborators","data.thematic-area","data.partners","data.beneficiaries","data.funding-source","data.project-status"];
fieldNames = ["sectors","countries","level of intervention","key collaborators","thematic areas","partners","beneficiaries","funding source","project status"];

fillOptions("#field1",fields,fieldNames,1);
fillOptions("#field2",fields,fieldNames,2);
updateChart();

// here the possible selections are defined
d3.select("#dataset").on("change",function(){
    filter = "";
    if(this.value == "substance_of_adaptation") {
        dataset = "substance_of_adaptation.json";
        fields = ["source","recipient_mapped","sector_mapped","donor","purpose","year"];
        fieldNames = ["source","countries","sectors in undp alm scheme","donor","purpose","year"];
    } else if(this.value == "undp") {
        dataset = "undp.json";
        fields = ["theme","location","data.level-of-intervention","data.key-collaborators","data.thematic-area","data.partners","data.beneficiaries","data.funding-source","data.project-status"];
        fieldNames = ["sectors","countries","level of intervention","key collaborators","thematic areas","partners","beneficiaries","funding source","project status"];
    /* 
        // alternatively we can get undp data through the following
        dataset = "adaptation_projects.json";
        fields = ["themes","countries","climate-hazards","key-collaborators"];
        fieldNames = ["sectors","countries","climate hazards","key collaborators"];
        filter = "undp";
        */
    } else if(this.value == "psi") {
        dataset = "adaptation_projects.json";
        fields = ["themes","countries","climate-hazards","key-collaborators"];
        fieldNames = ["sectors","countries","climate hazards","key collaborators"];
        filter = "psi";
    } else if(this.value == "climatewise") {
        dataset = "adaptation_projects.json";
        fields = ["themes","countries","climate-hazards","key-collaborators"];
        fieldNames = ["sectors","countries","climate hazards","key collaborators"];
        filter = "climatewise";
    } else if(this.value == "cigrasp") {
        dataset = "cigrasp.json";
        fields = ["overview.sector","country","types","scale","overview.stimuli","overview.impacts","project_classification.project_type","project_classification.project_status","project_classification.running_time","project_classification.spatial_scale","project_classification.effect_emergence","project_classification.effect_persistence","problem_solving_capacity_an_reversibility.problem_solving_coverage","problem_solving_capacity_an_reversibility.reversibility","responsibilities.initiating_agent","responsibilities.executing_agent","responsibilities.funding_source"];
        fieldNames = ["sectors","countries","types","scale","stimuli","impacts","project type","project status","running time","spatial scale","effect emergence","effect persistence","problem solving coverage","reversibility","initiating agent","executing agent","funding source"];
    } else if(this.value == "oecd") {
        dataset = "oecd.json";
        fields = [ "sector_mapped","recipientnameE","SectorNameE","donornameE", "agencynameE", "purposename_e", "RegionNameE", "IncomeGroupNameE"];
        fieldNames = ["sectors in undp alm scheme","recipient countries","sectors","donor countries","agency","purposes","regions","income Groups"];
    } else if(this.value == "climatefundsupdate") {
        dataset = "climatefundsupdate.json";
        fields = ["sector_mapped","recipient","recipient_income_level", "region", "donor", "implementor","sector"];
        fieldNames = ["sectors in undp alm scheme","recipient countries", "Recipient Income Level", "Region", "Funder", "Implementor","sectors"];
    } else if(this.value == "napa") {
        dataset = "napa.json";
        fields = ["sector_mapped","recipient","sector"];
        fieldNames = ["sectors in undp alm scheme","recipient","sectors"];
    }
    fillOptions("#field1",fields,fieldNames,1);
    fillOptions("#field2",fields,fieldNames,2);
    updateChart();
});
d3.select("#field1").on('change',function() {
    updateChart();
});

d3.select("#field2").on('change',function() {
    updateChart();
});
d3.selectAll('.radio').on('change', function(){
    orderby = this.value;
    updateChart();
});
d3.selectAll('.radiowhichdata').on('change', function(){
    whichdata = this.value;
    updateChart();
});
d3.select("#select1").style("display","block");
d3.select("#select2").style("display","block");


function updateChart() {
    d3.select("body").style("cursor","wait");
    
    var selectedSources = [],
    selectedTargets = [];

    d3.selectAll('#field1 option:checked').each(function(d){
        selectedSources.push(d);
    });
    d3.selectAll('#field2 option:checked').each(function(d){
        selectedTargets.push(d);
    });
    
    drawChart(orderby,dataset,selectedSources,selectedTargets,filter,fields,fieldNames);
    
    window.setTimeout(function() { // need for FF
        d3.select("body").style("cursor","default");
    },50);
}

function fillOptions(fieldid, fields, fieldNames, index) {
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
    
    d3.select(fieldid + " option:nth-child(" + index + ")").attr("selected","selected");
    
}

function drawChart(orderby,dataset,selectedSources,selectedTargets,filter,fields,fieldNames) {
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

            if(d.source == "undp" && i < 31) // The first 31 rows in the UNDP-ALM database were dropped and not used data calculations as they contain different records to the rest of the database and do not include details of projects.  Instead, they outline country-level NAP processes (for 24 countries) and details of P-CBA, at the  country-level (for 7 countries), with varying levels of detail.  They do not contain the same variables as the rest of the database. - see https://drive.google.com/?usp=folder&authuser=0#folders/0B3e-HpGNh9BwcVpPUHlJNkpnVWs
                return;

            if(filter != "" && d.source != filter)
                return;
            
            // parse varying formats of data from varying jsons and put them in the same format
            var dsources = [],
            dsource = "";
            selectedSources.forEach(function(source){
                if(source.indexOf(".")==-1)
                    dsource = d[source];
                else {
                    var tree = source.split(".");
                    dsource = d;
                    for(var i=0;i<tree.length;i++)
                        dsource = dsource[tree[i]]; // recurse instead of eval, as some object properties have dashes in their name
                }
                if(dsource !== "" && dsource !== undefined) {
                    if(!(dsource instanceof Array)) {
                        if(dataset == "cigrasp.json")
                            dsource = dsource.split(",");
                        else
                            dsource = [dsource];
                    }
                    dsources = dsources.concat(dsource);
                }
            });
            if(dsources.length < 1)
                return;
            
            var dtargets = [],
            dtarget = "";
            selectedTargets.forEach(function(target){
                if(target.indexOf(".")==-1)
                    dtarget = d[target];
                else {
                    var tree = target.split(".");
                    dtarget = d;
                    for(var i=0;i<tree.length;i++)
                        dtarget = dtarget[tree[i]];
                }
                
                if(dtarget !== "" && dtarget !== undefined) {
                    if(!(dtarget instanceof Array)) {
                        if(dataset == "cigrasp.json")
                            dtarget = dtarget.split(",");
                        else
                            dtarget = [dtarget];
                    }
                    dtargets = dtargets.concat(dtarget);
                }
            });
        
            if(dtargets.length < 1)
                return;
            
            // possibly filter data so that we only retain Bangladesh and India
            if(whichdata == 'indiabangladesh') {
                if('countries' in d && !(d.countries =='Bangladesh'||d.countries =='India'))
                    return;
                if('country' in d && !(d.country =='Bangladesh'||d.country =='India'))
                    return;
                if('recipientnameE' in d && !(d.recipientnameE =='Bangladesh'||d.recipientnameE =='India'))
                    return;
                if('recipient' in d && !(d.recipient =='Bangladesh'||d.recipient =='India'))
                    return;
                if('recipientMapped' in d && !(d.recipientMapped =='Bangladesh'||d.recipientMapped =='India'))
                    return;
            }

            // construct links between data
            dsources.forEach(function(s) {
                s = s.trim();
                if(s != 'Non-specific') {

                    var sid = nodeIndex(s,sources);
                    if(sid < 0) {
                        sources.push({
                            "name":s
                        });
                        sid = nodeIndex(s,sources);
                    }

                    dtargets.forEach(function(t) {
                        t = t.trim();
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


        // construct matrix of data
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

        // The default sort order of the visualization.
        if(orderby == "count") {
            x.domain(sourceOrders.count);
            y.domain(targetOrders.count);
        } else {
            x.domain(sourceOrders.name);
            y.domain(targetOrders.name);
        }

        // display visualization
        var row = svg.selectAll(".row")
        .data(matrix)
        .enter().append("g")
        .attr("class", "row")
        .attr("transform", function(d, i) {
            return "translate(0," + y(i)/2 + ")";
        })
        .each(row);

        row.append("text")
        .attr("x", -6)
        .attr("y", 2.5)
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
        .attr("y", 2.5)
        .attr("dy", ".32em")
        .attr("text-anchor", "start")
        .text(function(d, i) {
            return d.name;
        });
        
        // make csv of selection
        if(matrix.length > 0) {
            var csvArray = [], tmp = [];
            if(orderby == "count") {
                // header row
                tmp.push(" ");
                sourceOrders.count.forEach(function(s){
                    tmp.push(sources[s].name.replace(","," /"));
                });
                csvArray.push(tmp);
                // data rows
                targetOrders.count.forEach(function(t) {
                    // add data rows
                    tmp = [];
                    tmp.push(targets[t].name.replace(","," /"));
                    sourceOrders.count.forEach(function(s){
                        tmp.push(matrix[t][s].z); 
                    })
                    csvArray.push(tmp);
                });
            } else {
                // header row
                tmp.push(" ");
                sourceOrders.name.forEach(function(s){
                    tmp.push(sources[s].name.replace(","," /"));
                });
                csvArray.push(tmp);
                // data rows
                targetOrders.name.forEach(function(t) {
                    // add data rows
                    tmp = [];
                    tmp.push(targets[t].name.replace(","," /"));
                    sourceOrders.name.forEach(function(s){
                        tmp.push(matrix[t][s].z); 
                    })
                    csvArray.push(tmp);
                });
            }
        
            // put csv in download link
            var csv_target_name = "", csv_source_name = "";
            selectedTargets.forEach(function(target){
                if(csv_target_name === "")
                    csv_target_name = fieldNames[fields.indexOf(target)];
                else
                    csv_target_name = csv_target_name + "_" + fieldNames[fields.indexOf(target)];
            });
            selectedSources.forEach(function(source){
                if(csv_source_name === "")
                    csv_source_name = fieldNames[fields.indexOf(source)];
                else
                    csv_source_name = csv_source_name + "_" + fieldNames[fields.indexOf(source)];
            });
            var datasetname = dataset.replace(".json","");
            if(filter !== "")
                datasetname = filter;
            if(datasetname == "substance_of_adaptation")
                datasetname = "combined_dataset";
            var a = document.createElement('a');
            a.innerHTML = "Download CSV of selection";
            a.href     = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csvArray.join('\n'));
            a.target   = '_blank';
            a.download =  datasetname + "-" + csv_target_name + "-" + csv_source_name + "-" + whichdata + ".csv";
            d3.select('#csv a').remove();
            document.getElementById('csv').appendChild(a);
        }
        
        // find key for a given value
        function nodeIndex(name, list) {
            var i;
            for(i=0;i<list.length;i++) {
                if(list[i].name == name)
                    return i;
            }
            return -1;
        }

        // find key for a given value
        function linkIndex(source, target, list) {
            var i;
            for(i=0;i<list.length;i++) {
                if(list[i].source == source && list[i].target == target)
                    return i;
            }
            return -1;
        }

        // draw one row of data
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
