'use strict';

var tartanaApp = angular.module('tartanaApp', [ 'ngRoute', 'mgcrea.ngStrap', 'emguo.poller', 'tartanaControllers', 'tartana.services' ]);

tartanaApp.config([ '$routeProvider', function($routeProvider) {
	$routeProvider.when('/', {
		templateUrl : GLOABL_ROUTES['dashboard'],
		controller : 'DashboardCtrl'
	});
	$routeProvider.when('/login', {
		templateUrl : GLOABL_ROUTES['login'],
		controller : 'LoginCtrl'
	});
	$routeProvider.when('/downloads', {
		templateUrl : GLOABL_ROUTES['downloads'],
		controller : 'DownloadsCtrl'
	});
	$routeProvider.when('/parameters', {
		templateUrl : GLOABL_ROUTES['parameters'],
		controller : 'ParametersCtrl'
	});
	$routeProvider.when('/logs', {
		templateUrl : GLOABL_ROUTES['logs'],
		controller : 'LogsCtrl'
	});
	$routeProvider.when('/profile', {
		templateUrl : GLOABL_ROUTES['profile'],
		controller : 'LogCtrl'
	});
	$routeProvider.otherwise({
		redirectTo : '/'
	});
} ]);

tartanaApp.factory('TartanaInterceptor', [ '$rootScope', '$q', '$injector', '$location', 'localStorageService',
		function($rootScope, $q, $injector, $location, localStorageService) {
			return {
				request : function(config) {
					$rootScope.loading = true;
					var tokenHandler = $injector.get('TokenHandler');
					tokenHandler.prepareRootScope($rootScope);
					if (typeof $rootScope.userAuth != "undefined") {
						config.headers['X-WSSE'] = tokenHandler.getCredentials($rootScope.userAuth.username, $rootScope.userAuth.secret);
					}
					return config;
				},
				response : function(response) {
					$rootScope.loading = false;
					return response;
				},
				responseError : function(rejection) {
					$rootScope.userAuth = undefined;
					if (rejection.status === 403) {
						localStorageService.remove('tartana.username');
						localStorageService.remove('tartana.secret');
						localStorageService.remove('tartana.authtime');
						$rootScope.errorMsg = rejection.data;
						$location.path('/login');
					}
					return $q.reject(rejection);
				}
			};
		} ]);
tartanaApp.config(function($httpProvider) {
	$httpProvider.interceptors.push('TartanaInterceptor');
});

tartanaApp.filter('unique', function() {
	return function(input, key) {
		if (typeof input == 'undefined') {
			return;
		}
		var unique = {};
		var uniqueList = [];
		for (var i = 0; i < input.length; i++) {
			if (typeof unique[input[i][key]] == "undefined") {
				unique[input[i][key]] = "";
				uniqueList.push(input[i]);
			}
		}
		return uniqueList;
	};
});

tartanaApp.filter('startFrom', function() {
	return function(input, start) {
		if (input) {
			start = +start;
			return input.slice(start);
		}
		return [];
	};
});

tartanaApp.directive('wtResponsiveTable', function wtResponsiveTable() {
	return {
		restrict : 'A',
		compile : function(element, attrs) {
			attrs.$addClass('responsive');
			var headers = element[0].querySelectorAll('tr > th');
			if (headers.length) {
				var rows = element[0].querySelectorAll('tbody > tr');
				Array.prototype.forEach.call(rows, function(row) {
					var headerIndex = 0;
					Array.prototype.forEach.call(row.querySelectorAll('td'), function(value, index) {
						var th = value.parentElement.querySelector('th') || headers.item(headerIndex);
						var title = th.textContent;
						if (title && !value.getAttributeNode('data-title')) {
							value.setAttribute('data-title', title);
						}

						var colspan = value.getAttributeNode('colspan');
						headerIndex += colspan ? parseInt(colspan.value) : 1;
					});
				});
			}
		}
	};
});

tartanaApp.run([ '$rootScope', '$location', '$http', 'TokenHandler', function($rootScope, $location, $http, tokenHandler) {
	$rootScope.numPerPage = GLOBALS['listLength'];
	$rootScope.$on('$locationChangeStart', function(event, next, current) {
		tokenHandler.prepareRootScope($rootScope);
		if ($location.path() !== '/login' && typeof $rootScope.userAuth == "undefined") {
			$location.path('/login');
		} else if (!$rootScope.user && typeof $rootScope.userAuth != "undefined") {
			$http.get(GLOABL_ROUTES['api_v1_user_find'], {
				params : {
					username : $rootScope.userAuth.username
				}
			}).then(function(response) {
				if (response.data.data.length) {
					$rootScope.user = response.data.data[0];
				}
			});
		}
	});
} ]);