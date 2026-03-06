<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Panel
    |--------------------------------------------------------------------------
    |
    | The default panel ID used when no panel is specified.
    |
    */
    'default_panel' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class used for authentication in Vuelament panels.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | App Path
    |--------------------------------------------------------------------------
    |
    | Base directory path (relative to app/) for Vuelament user modules.
    |
    */
    'app_path' => 'Vuelament',

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | Configuration for frontend asset handling.
    |
    */
    'assets' => [
        'auto_publish' => true,
    ],

];
