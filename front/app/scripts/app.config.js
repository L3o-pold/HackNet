(function() {
    'use strict';

    angular.module('hackNet')
        .config(['$routeProvider', '$locationProvider', function ($routeProvider, $locationProvider) {
            $routeProvider
                .when('/', {
                    templateUrl: 'views/main.html',
                    controller: 'MainCtrl'
                }).when('/access_token=:accessToken', {
                    template: '',
                    controller: function ($location, AccessToken) {
                        var hash = $location.path().substr(1);
                        AccessToken.setTokenFromString(hash);
                        $location.path('/');
                        $location.replace();
                    }
                }).otherwise({
                    redirectTo: '/'
                });
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
