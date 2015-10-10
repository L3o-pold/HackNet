(function () {
    'use strict';

    var app = angular.module('hackNet', [
        'ngRoute',
        'UserApp',
        'ui.bootstrap',
        'vtortola.ng-terminal',
        'ng-terminal-example.command.tools',
        'ng-terminal-example.command.implementations',
        'ng-terminal-example.command.filesystem'
    ]);

    app.run(function($rootScope, user) {
        user.init({ appId: '56191c36bd950' });
    });

})();
