'use strict';

var serviceApp = angular.module('tartana.services', [ 'ngResource' ]);
serviceApp.factory('Base64', function() {
	var keyStr = 'ABCDEFGHIJKLMNOP' + 'QRSTUVWXYZabcdef' + 'ghijklmnopqrstuv' + 'wxyz0123456789+/' + '=';
	return {
		encode : function(input) {
			var output = "";
			var chr1, chr2, chr3 = "";
			var enc1, enc2, enc3, enc4 = "";
			var i = 0;

			do {
				chr1 = input.charCodeAt(i++);
				chr2 = input.charCodeAt(i++);
				chr3 = input.charCodeAt(i++);

				enc1 = chr1 >> 2;
				enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
				enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
				enc4 = chr3 & 63;

				if (isNaN(chr2)) {
					enc3 = enc4 = 64;
				} else if (isNaN(chr3)) {
					enc4 = 64;
				}

				output = output + keyStr.charAt(enc1) + keyStr.charAt(enc2) + keyStr.charAt(enc3) + keyStr.charAt(enc4);
				chr1 = chr2 = chr3 = "";
				enc1 = enc2 = enc3 = enc4 = "";
			} while (i < input.length);

			return output;
		},

		decode : function(input) {
			var output = "";
			var chr1, chr2, chr3 = "";
			var enc1, enc2, enc3, enc4 = "";
			var i = 0;

			// remove all characters that are not A-Z, a-z, 0-9, +, /,
			// or =
			var base64test = /[^A-Za-z0-9\+\/\=]/g;
			if (base64test.exec(input)) {
				alert("There were invalid base64 characters in the input text.\n" + "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n"
						+ "Expect errors in decoding.");
			}
			input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

			do {
				enc1 = keyStr.indexOf(input.charAt(i++));
				enc2 = keyStr.indexOf(input.charAt(i++));
				enc3 = keyStr.indexOf(input.charAt(i++));
				enc4 = keyStr.indexOf(input.charAt(i++));

				chr1 = (enc1 << 2) | (enc2 >> 4);
				chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
				chr3 = ((enc3 & 3) << 6) | enc4;

				output = output + String.fromCharCode(chr1);

				if (enc3 != 64) {
					output = output + String.fromCharCode(chr2);
				}
				if (enc4 != 64) {
					output = output + String.fromCharCode(chr3);
				}

				chr1 = chr2 = chr3 = "";
				enc1 = enc2 = enc3 = enc4 = "";

			} while (i < input.length);

			return output;
		}
	};
});
serviceApp.factory('TokenHandler', [
		'$http',
		'Base64',
		'localStorageService',
		function($http, Base64, localStorageService) {
			var tokenHandler = {};
			var token = 'none';

			tokenHandler.set = function(newToken) {
				token = newToken;
			};

			tokenHandler.get = function() {
				return token;
			};

			tokenHandler.prepareRootScope = function($rootScope) {
				if (typeof localStorageService.get('tartana.username') != "undefined"
						&& typeof localStorageService.get('tartana.secret') != "undefined"
						&& typeof localStorageService.get('tartana.authtime') != "undefined") {
					if (moment(localStorageService.get('tartana.authtime')).add(300, 'seconds').isAfter(moment())) {
						$rootScope.userAuth = {
							username : localStorageService.get('tartana.username'),
							secret : localStorageService.get('tartana.secret')
						};
						localStorageService.set('tartana.authtime', moment().format());
					}
				}
			};
			// Generate random string of length
			tokenHandler.randomString = function(length) {
				var text = "";
				var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
				for (var i = 0; i < length; i++) {
					text += possible.charAt(Math.floor(Math.random() * possible.length));
				}
				return text;
			};

			tokenHandler.getCredentials = function(username, secret) {
				// Generate nonce
				var nonce = tokenHandler.randomString(30);

				// Creation time of the token
				var created = moment().format();

				// Generating digest from secret, creation and nonce
				var hash = CryptoJS.SHA1(nonce + created + secret);
				var digest = hash.toString(CryptoJS.enc.Base64);

				// Base64 Encode digest
				var b64nonce = Base64.encode(nonce);

				// Return generated token
				return 'UsernameToken Username="' + username + '", PasswordDigest="' + digest + '", Nonce="' + b64nonce + '", Created="' + created
						+ '"';
			};

			return tokenHandler;
		} ]);
serviceApp.factory('Digest', [ '$q', function($q) {
	var factory = {
		// Symfony SHA512 encryption provider
		cipher : function(secret, salt) {
			var deferred = $q.defer();

			var salted = secret + '{' + salt + '}';
			var digest = CryptoJS.SHA512(salted);
			for (var i = 1; i < 5000; i++) {
				digest = CryptoJS.SHA512(digest.concat(CryptoJS.enc.Utf8.parse(salted)));
			}
			digest = digest.toString(CryptoJS.enc.Base64);

			deferred.resolve(digest);
			return deferred.promise;
		},
		// Default Symfony plaintext encryption provider
		plain : function(secret, salt) {
			var deferred = $q.defer();

			var salted = secret + '{' + salt + '}';
			var digest = salted;

			deferred.resolve(digest);
			return deferred.promise;
		}
	};
	return factory;
} ]);
