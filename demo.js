var http = require('http');
var querystring = require('querystring');

var fs = require('fs');

var configFilename = 'config.json';

function matchUriTemplate(template, uri) {
	var params = {};
	var parts = template.split("{");
	
	var initialPart = parts.shift();
	if (uri.substring(0, initialPart.length) != initialPart) {
		return null;
	}
	uri = uri.substring(initialPart.length);
	
	while (parts.length > 0) {
		var part = parts.shift();
		var varname = part.substring(0, part.indexOf('}'));
		var remainder = part.substring(varname.length + 1);
		var index = (remainder == '') ? uri.length : uri.indexOf(remainder);
		if (index == -1) {
			return null;
		}
		params[varname] = uri.substring(0, index);
		uri = uri.substring(index + remainder.length);
		console.log(uri);
	}
	return uri == "" ? params : null;
}

function matchConditions(request, where) {
	if (where.method) {
		if (Array.isArray(where.method)) {
			if (where.method.indexOf(request.method) == -1) {
				return false;
			}
		} else if (where.method != request.method) {
			return false;
		}
	}
	if (where.path) {
		if (!matchUriTemplate(where.path, request.path)) {
			return false;
		}
	}
	if (where.query) {
		for (var key in where.query) {
			if (request.query[key] != where.query[key]) {
				return false;
			}
		}
	}
	return true;
}

var server = http.createServer(function (request, response) {
	fs.readFile(configFilename, function (error, data) {
		var config = JSON.parse(data);
		
		request.path = request.url;
		request.query = {};
		if (request.url.indexOf('?') != -1) {
			request.path = request.url.substring(0, request.url.indexOf('?'));
			request.query = querystring.parse(request.url.substring(request.url.indexOf('?') + 1));
		}
		
		for (var i = 0; i < config.matches.length; i++) {
			var match = config.matches[i];
			var params;
			if (params = matchConditions(request, match.where)) {
				response.end(JSON.stringify(match.data, null, 4));
				return;
			}
		}
		
		response.statusCode = 404;
		response.end(JSON.stringify({"statusCode":404,"statusText":"Not Found","message":"Page not found"}, null, "\t"));
	});
});
server.listen(8080);