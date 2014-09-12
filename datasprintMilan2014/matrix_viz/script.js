/*
 * Author Erik Borra <erik@digitalmethods.net>
 * Based on http://bost.ocks.org/mike/miserables/
 */

var orderby = "count"; // count or alphabet
var dataset = "substance_of_adaptation.json";
drawChart(orderby,dataset);

function drawChart(orderby,dataset) {
    var margin = {
        top: 30, 
        right: 0, 
        bottom: 10, 
        left: 140
    },
    width = 720,
    height = 1200;

    var x = d3.scale.ordinal().rangeBands([0, width]),
    y = d3.scale.ordinal().rangeBands([0, height]);

    var svg = d3.select("body").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
    .style("margin-left", -margin.left + "px")
    .append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    d3.json(dataset, function(datasets) {
        var nodes = [],
        links = [],
        sources = [],
        targets = [];
    
        if(dataset == "substance_of_adaptation.json") {
            // compute index per node
            datasets.forEach(function(d,i) {
                var sourceId = nodeIndex(d.source,sources);
                if(sourceId < 0) {
                    sources.push({
                        "name":d.source
                    })
                    sourceId = nodeIndex(d.source,sources);
                }
                d.recipient_mapped.forEach(function(r) {
                    if(r != 'Non-specific') {
                        var rid = nodeIndex(r,targets);
                        if(rid < 0) {
                            targets.push({
                                "name":r
                            });
                            rid = nodeIndex(r,targets);
                        }
                
                        var li = linkIndex(sourceId, rid, links);
                        if(li < 0)
                            links.push({
                                "source":sourceId,
                                "target":rid,
                                "value":1
                            });
                        else
                            links[li].value++;
                    }
                });
            });
        }
    
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
        z = d3.scale.linear().domain([0, max]); // opacity
    
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
        .attr("y", y.rangeBand()/2)
        .attr("dy", ".12em")
        .attr("transform", function(d, i) {
            return "translate(0," + y(i)/2 + ")";
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
            return "translate(" + x(i) + ",-10)";
        });

        column.append("text")
        .attr("x", 6)
        .attr("y", y.rangeBand() / 2)
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
                return z(d.z) * x.rangeBand()
            })
            .attr("height", y.rangeBand()/2)
            .style("fill", function(d,i) {
                return "rgb(31, 119, 180)";
            })
            .on("mouseover", mouseover)
            .on("mouseout", mouseout)
            .append("svg:title")
            .text(function(d) {
                return d.z;
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
