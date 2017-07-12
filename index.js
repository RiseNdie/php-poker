const http = require('http');

var
	hostname        = '127.0.0.1';
    gameport        = process.env.PORT || 9510,

    io              = require('socket.io'),
    express         = require('express'),
    UUID            = require('node-uuid'),

    verbose         = false,
    app             = module.exports.app = express();

var server = http.createServer(app);

server.listen( gameport, hostname );

app.get( '/', function( req, res ){
    res.sendFile( __dirname + '/templates/poker.html' );
});

var sio = io.listen(server);
var players = [];
var request = require("request");

sio.sockets.on('connection', function (client) {

    client.userid = UUID();
    players.push(client.userid);

    client.emit('onconnected', { id: client.userid } );

    client.on('disconnect', function () {
        var i = players.indexOf(client.userid);
        players.splice(i, 1);

        request({
            uri: "http://games.igaming.loc:9509",
            method: "POST",
            form: { action : 'user_leave' , id: client.userid }
        });
    });
});