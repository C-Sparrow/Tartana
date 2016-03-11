'use strict';

var tartanaControllers = angular.module('tartanaControllers', [ 'ngFileUpload', 'LocalStorageModule', 'emguo.poller', 'ui.bootstrap' ]);
tartanaControllers.config(function($interpolateProvider) {
	$interpolateProvider.startSymbol('{[').endSymbol(']}');
});

tartanaControllers.config(function(pollerConfig) {
	pollerConfig.resetOn = '$stateChangeStart';
	pollerConfig.resetOn = '$routeChangeStart';
});

tartanaControllers.controller('DashboardCtrl', [ '$rootScope', '$scope', 'poller', function($rootScope, $scope, poller) {
	$rootScope.successMsg = null;
	$rootScope.errorMsg = null;

	$scope.filteredDownloads = [], $scope.currentPage = 1, $scope.numPerPage = 10, $scope.maxSize = 5;
	$scope.$watch('currentPage + numPerPage', function() {
		if (!$scope.downloads) {
			return;
		}
		var begin = (($scope.currentPage - 1) * $scope.numPerPage), end = begin + $scope.numPerPage;

		$scope.filteredDownloads = $scope.downloads.slice(begin, end);
	});

	var myPoller = poller.get(GLOABL_ROUTES['api_v1_download_find'] + '/2,6', {
		delay : 2000,
		smart : true
	});

	myPoller.promise.then(null, null, function(data) {
		var downloads = data.data.data;
		if (downloads == null) {
			downloads = [];
		}

		for (var index = 0; index < downloads.length; ++index) {
			if (downloads[index]['started_at']) {
				downloads[index]['started_at']['dateFormated'] = moment(downloads[index]['started_at'].date).formatPHP(GLOBALS['dateFormat']);
			}
			if (downloads[index]['finished_at']) {
				downloads[index]['finished_at']['dateFormated'] = moment(downloads[index]['finished_at'].date).formatPHP(GLOBALS['dateFormat']);
			}
		}
		$scope.downloads = downloads;

		if (!$scope.downloads.length) {
			$rootScope.errorMsg = GLOABL_STRINGS['TARTANA_VIEW_DASHBOARD_MESSAGE_NO_RUNNING_DOWNLOADS_FOUND'];
		} else {
			$rootScope.successMsg = null;
			$rootScope.errorMsg = null;
		}
	});
} ]);

tartanaControllers.controller('LoginCtrl', [ '$rootScope', '$scope', '$http', '$location', 'localStorageService', 'Digest',
		function($rootScope, $scope, $http, $location, localStorageService, Digest) {
			$rootScope.errorMsg = GLOABL_STRINGS['TARTANA_VIEW_LOGIN_TEXT_NEEDS_LOGIN'];

			$scope.getSalt = function() {
				var username = $scope.username;
				var password = $scope.password;

				$http.get(GLOABL_ROUTES['api_v1_user_salt'], {
					params : {
						username : username
					}
				}).then(function(response) {
					if (!response.data.success) {
						$rootScope.errorMsg = response.data.message;
						return;
					}
					var salt = response.data.data.salt;

					// Encrypt password accordingly to generate secret
					Digest.cipher(password, salt).then(function(secret) {
						// Display salt and secret for this example
						$scope.salt = salt;
						$scope.secret = secret;
						// Store auth informations in cookies for page refresh
						localStorageService.set('tartana.username', $scope.username);
						localStorageService.set('tartana.secret', $scope.secret);
						localStorageService.set('tartana.authtime', moment().format());
						// Store auth informations in rootScope for multi views
						// access
						$rootScope.userAuth = {
							username : $scope.username,
							secret : $scope.secret
						};
						$location.path('/');
					}, function(err) {
						$rootScope.errorMsg = err;
					});
				});
			};
		} ]);

tartanaControllers.controller('DownloadsCtrl', [ '$rootScope', '$scope', '$http', 'poller', function($rootScope, $scope, $http, poller) {
	$rootScope.successMsg = null;
	$rootScope.errorMsg = null;

	$scope.clearAll = function() {
		$http({
			url : GLOABL_ROUTES['api_v1_download_clearall']
		})
	}
	$scope.clearCompleted = function() {
		$http({
			url : GLOABL_ROUTES['api_v1_download_clearcompleted']
		})
	}
	$scope.resumeFailed = function() {
		$http({
			url : GLOABL_ROUTES['api_v1_download_resumefailed']
		})
	}
	$scope.resumeAll = function() {
		$http({
			url : GLOABL_ROUTES['api_v1_download_resumeall']
		})
	}
	$scope.reprocess = function() {
		$http({
			url : GLOABL_ROUTES['api_v1_download_reprocess']
		})
	}

	var myPoller = poller.get(GLOABL_ROUTES['api_v1_download_find'], {
		delay : 2000,
		smart : true
	});

	myPoller.promise.then(null, null, function(data) {
		var downloads = data.data.data;
		if (downloads == null) {
			downloads = [];
		}
		for (var index = 0; index < downloads.length; ++index) {
			if (downloads[index]['started_at']) {
				downloads[index]['started_at']['dateFormated'] = moment(downloads[index]['started_at'].date).formatPHP(GLOBALS['dateFormat']);
			}
			if (downloads[index]['finished_at']) {
				downloads[index]['finished_at']['dateFormated'] = moment(downloads[index]['finished_at'].date).formatPHP(GLOBALS['dateFormat']);
			}
		}
		$scope.downloads = downloads;

		if (!$scope.downloads.length) {
			$rootScope.errorMsg = GLOABL_STRINGS['TARTANA_TEXT_NO_ITEMS_FOUND'];
		} else {
			$rootScope.successMsg = null;
			$rootScope.errorMsg = null;
		}
	});
} ]);

tartanaControllers.controller('AddCtrl', [ '$rootScope', '$scope', 'Upload', '$timeout', function($rootScope, $scope, Upload, $timeout) {
	$rootScope.successMsg = null;
	$rootScope.errorMsg = null;

	$scope.uploadDlc = function(file) {
		file.upload = Upload.upload({
			url : GLOABL_ROUTES['api_v1_file_add'],
			data : {
				file : file,
				username : $scope.username
			}
		});

		file.upload.then(function(response) {
			$timeout(function() {
				file.result = response.data;

				if (response.status == 200 && response.data.success) {
					$rootScope.successMsg = response.data.message;
				} else {
					$rootScope.errorMsg = response.data.message;
				}
			});
		}, function(response) {
			if (response.status > 0) {
				$rootScope.errorMsg = response.data.message;
			}
		}, function(evt) {
			// Math.min is to fix IE which reports 200%
			// sometimes
			file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
		});
	}
} ]);

tartanaControllers.controller('ParametersCtrl', [ '$rootScope', '$scope', '$http', 'poller', function($rootScope, $scope, $http, poller) {
	$rootScope.successMsg = null;
	$rootScope.errorMsg = null;

	$scope.setParameter = function() {
		$http({
			method : 'POST',
			url : GLOABL_ROUTES['api_v1_parameter_set'],
			data : encodeURIComponent(this.parameter.key) + '=' + (this.parameter.value),
			headers : {
				'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8'
			}
		}).then(function(response) {
			if (response.data.success) {
				$rootScope.successMsg = response.data.message;
				$rootScope.errorMsg = null;
			} else {
				$rootScope.successMsg = null;
				$rootScope.errorMsg = response.data.message;
			}
		});
	};

	$http({
		url : GLOABL_ROUTES['api_v1_parameter_find']
	}).then(function(response) {
		var parameters = response.data.data;
		if (parameters == null) {
			parameters = [];
		}

		$scope.parameters = parameters;

		if (!$scope.parameters.length) {
			$rootScope.errorMsg = GLOABL_STRINGS['TARTANA_TEXT_NO_ITEMS_FOUND'];
		} else {
			$rootScope.successMsg = null;
			$rootScope.errorMsg = null;
		}
	});
} ]);

tartanaControllers.controller('LogsCtrl', [ '$rootScope', '$scope', '$http', 'poller', function($rootScope, $scope, $http, poller) {
	$rootScope.successMsg = null;
	$rootScope.errorMsg = null;

	$scope.deleteLogs = function() {
		$http({
			url : GLOABL_ROUTES['api_v1_log_deleteall']
		})
	}

	var myPoller = poller.get(GLOABL_ROUTES['api_v1_log_find'], {
		delay : 2000,
		smart : true
	});

	myPoller.promise.then(null, null, function(data) {
		var logs = data.data.data;
		if (logs == null) {
			logs = [];
		}
		for (var index = 0; index < logs.length; ++index) {
			var log = logs[index];
			if (log['date']) {
				log['date']['dateFormated'] = moment(log['date'].date).formatPHP(GLOBALS['dateFormat']);
			}
			log['id'] = CryptoJS.MD5(JSON.stringify(log)).toString();
		}
		$scope.logs = logs;

		if (!$scope.logs.length) {
			$rootScope.errorMsg = GLOABL_STRINGS['TARTANA_TEXT_NO_ITEMS_FOUND'];
		} else {
			$rootScope.successMsg = null;
			$rootScope.errorMsg = null;
		}
	});
} ]);