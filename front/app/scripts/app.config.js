(function() {
    'use strict';

    angular.module('hackNet')
        .config(['$routeProvider', '$locationProvider', function ($routeProvider, $locationProvider) {
            $routeProvider.when('/login', {templateUrl: 'views/login.html', login: true});
            $routeProvider.when('/signup', {templateUrl: 'views/signup.html', public: true});
            $routeProvider.when('/home', {templateUrl: 'views/home.html', controller: 'MainCtrl', public: false});
            $routeProvider.when('/verify-email', {templateUrl: 'verify-email.html', verify_email: true});
            $routeProvider.when('/reset-password', {templateUrl: 'reset-password.html', public: true});
            $routeProvider.when('/set-password', {templateUrl: 'set-password.html', set_password: true});
            $routeProvider.otherwise({redirectTo: '/home'});
        }])
        .config(['terminalConfigurationProvider',
            function (terminalConfigurationProvider) {

                terminalConfigurationProvider.config('vintage').outputDelay = 10;
                terminalConfigurationProvider.config('vintage').allowTypingWriteDisplaying =
                    false;
                terminalConfigurationProvider.config('vintage').typeSoundUrl =
                    'example/content/type.wav';
                terminalConfigurationProvider.config('vintage').startSoundUrl =
                    'example/content/start.wav';
            }]);
})();
