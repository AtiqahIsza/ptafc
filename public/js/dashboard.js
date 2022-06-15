$( window ).on('load', function() {
    getPassengerType();
    getCollectionByCompanyBar();
    $("#pressToRefresh").one('click', function (event) {
        event.preventDefault();
    });
});
function getPassengerType()
{
    $('#pie-chart').empty();
    $.ajax({
        url: "/home/getPassengerType",
        success:function(response)
        {
            if(response) {
                $('.success').text(response.success);
                console.log(response.statusDescription)
                if (response.statusCode === 1)
                {
                    let passengerType = response.payload;
                    updateCurrentOnlineView(passengerType);
                }
                else
                {
                    if (response.statusCode === 2)
                    {

                        $('#pie-chart').append("No Ridership In This Month");
                    }
                    else
                    {
                        $('#pie-chart').append("Unavailable");
                    }
                }
            }
        },
        error:function (response)
        {
            console.log(response);
        }
    });
}
function getCollectionByCompanyBar() {
    $('#bar-chart').empty();
    $.ajax({
        url: "/home/getCollectionByCompanyBar",
        success: function (response) {
            if (response) {
                $('.success').text(response.success);
                console.log(response.statusDescription)
                if (response.statusCode === 1) {
                    let collectionCompanyName = response.payload.company_name;
                    let collectionData = response.payload.farebox_ridership;
                    updateCollectionByCompanyBarView(collectionCompanyName,collectionData);
                } else {
                    if (response.statusCode === 2) {
                        $('#bar-chart').append("None");
                    } else {
                        $('#bar-chart').append("Unavailable");
                    }
                }
            }
        },
        error: function (response) {
            console.log(response);
        }
    });
}
function updateCollectionByCompanyBarView(collectionCompanyName,collectionData)
{
    $(function() {
        var data = {
            labels: collectionCompanyName,
            series: collectionData
        };

        var options = {
            seriesBarDistance: 15,
            plugins: [
                Chartist.plugins.tooltip({
                    appendToBody: true
                })
            ]
        };

        var responsiveOptions = [
            ['screen and (min-width: 641px) and (max-width: 1024px)', {
                seriesBarDistance: 10,
                axisX: {
                    labelInterpolationFnc: function (value) {
                        return value;
                    }
                }
            }],
            ['screen and (max-width: 640px)', {
                seriesBarDistance: 10,
                axisX: {
                    labelInterpolationFnc: function (value) {
                        return value[0];
                    }
                }
            }]
        ];

        new Chartist.Bar('.bar-chart', data, options, responsiveOptions);
    });
}
function updateCurrentOnlineView(passengerType)
{
    //alert(passengerType);
    $(function() {
        var data = {
            series: passengerType
        };

        var sum = function(a, b) { return a + b };

        new Chartist.Pie('.pie-chart', data, {
            labelInterpolationFnc: function(value) {
                return Math.round(value / data.series.reduce(sum) * 100) + '%';
            }
        });
    });
}
