var casper = require('casper').create({
    logLevel: "debug",              // Only "info" level messages will be logged
    verbose: false                   // log messages will be printed out to the console
});


if (casper.cli.args.length!=2) {
    casper.echo("");
    casper.echo("Thats not formatted right");
    casper.echo("Please follow this format:");
    casper.echo("");
    casper.echo("casperjs scheduler.js [email] [password]");
    casper.echo("");
    casper.echo("");
    casper.exit();
}

var email = casper.cli.args[0];
var password = casper.cli.args[1];
var url_encoded_image_url = casper.cli[2];
var url_encoded_url = casper.cli[3];
var url_encoded_description = casper.cli[4];



casper.start('https://www.pinterest.com/login/', function () {

    this.fill('form.loginForm', {
        'username_or_email': email,
        'password': password
    }, true);

    console.log('Filled form with email & password')
});


casper.then(function () {
    this.click('.formFooterButtons button');
    console.log('Pressed login button');
});

casper.then(function () {

    if (casper.exists('.loginError p')) {
        this.echo(this.getHTML('.loginError p'));
        this.exit();
    }
});


casper.then(function () {

this.open(
    'http://www.pinterest.com/pin/create/bookmarklet/'+
        '?media=' +
        'http%3A%2F%2Fimages.ak.instagram.com%2Fprofiles%2FanonymousUser.jpg'+
        '&url=' +
        'http%3A%2F%2Finstagram.com%2Fdeveloper%2Fendpoints%2Fmedia%2F' +
        '&description='+
        'Media%20Endpoints%20%E2%80%A2%20Instagram%20Developer%20Documentation'
    );
    this.reload(function() {
        console.log('Logged in, new location is ' + this.getCurrentUrl());
    });
});

casper.run();