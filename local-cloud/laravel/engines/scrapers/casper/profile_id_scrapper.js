var casper = require('casper').create({
    logLevel: "debug",             // Only "info" level messages will be logged
    verbose: false                 // log messages will be printed out to the console
});

casper.userAgent('Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7A341 Safari/528.16');


if (casper.cli.args.length!=1) {
    casper.echo("");
    casper.echo("Thats not formatted right");
    casper.echo("Please follow this format:");
    casper.echo("");
    casper.echo("casperjs profile_id_scrapper.js [username] ");
    casper.echo("");
    casper.echo("");
    casper.exit();
}

var username = casper.cli.args[0];

casper.start('https://www.pinterest.com/' + username + '/', function () {

    var js = this.evaluate(function() {
        return $('#jsInit').html();

    });

    js = js.replace('P.scout.init(','var scout = function() { return ');
    js = js.replace('});','}; };');
    js = js.replace('P.start.start(','var data = function() { return ');
    js = js.replace(');','}; data();');

    var index = js.indexOf('var data = function() {');
    var scout = js.substr(0,index);
    last_index = scout.lastIndexOf(',');

    scout = scout.substr(0, last_index) +  scout.substr(last_index+1);

    scout = scout.replace('};','}; scout();');


    //var first = eval(scout);

    json = eval(js);

    //json = JSON.parse(js);

    console.log(json.tree.children[2].children[2].children[0].children[0].data[0].id);
});

casper.run();