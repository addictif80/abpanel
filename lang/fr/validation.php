<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'accepted'             => 'Le champ :attribute doit être accepté.',
    'accepted_if'          => 'Le champ :attribute doit être accepté quand :other vaut :value.',
    'active_url'           => 'Le champ :attribute n\'est pas une URL valide.',
    'after'                => 'Le champ :attribute doit être une date postérieure à :date.',
    'after_or_equal'       => 'Le champ :attribute doit être une date postérieure ou égale à :date.',
    'alpha'                => 'Le champ :attribute ne doit contenir que des lettres.',
    'alpha_dash'           => 'Le champ :attribute ne doit contenir que des lettres, des chiffres, des tirets et des underscores.',
    'alpha_num'            => 'Le champ :attribute ne doit contenir que des lettres et des chiffres.',
    'array'                => 'Le champ :attribute doit être un tableau.',
    'before'               => 'Le champ :attribute doit être une date antérieure à :date.',
    'before_or_equal'      => 'Le champ :attribute doit être une date antérieure ou égale à :date.',
    'between'              => [
        'array'   => 'Le champ :attribute doit avoir entre :min et :max éléments.',
        'file'    => 'Le fichier :attribute doit être compris entre :min et :max kilobytes.',
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string'  => 'Le champ :attribute doit être compris entre :min et :max caractères.',
    ],
    'boolean'              => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed'            => 'Le champ de confirmation :attribute ne correspond pas.',
    'current_password'     => 'Le mot de passe est incorrect.',
    'date'                 => 'Le champ :attribute n\'est pas une date valide.',
    'date_equals'          => 'Le champ :attribute doit être une date égale à :date.',
    'date_format'          => 'Le champ :attribute ne correspond pas au format :format.',
    'decimal'              => 'Le champ :attribute doit avoir :decimal décimales.',
    'declined'             => 'Le champ :attribute doit être refusé.',
    'different'            => 'Les champs :attribute et :other doivent être différents.',
    'digits'               => 'Le champ :attribute doit contenir :digits chiffres.',
    'digits_between'       => 'Le champ :attribute doit contenir entre :min et :max chiffres.',
    'email'                => 'Le champ :attribute doit être une adresse email valide.',
    'ends_with'            => 'Le champ :attribute doit se terminer par une des valeurs suivantes : :values.',
    'exists'               => 'La valeur sélectionnée pour :attribute est invalide.',
    'file'                 => 'Le champ :attribute doit être un fichier.',
    'filled'               => 'Le champ :attribute doit avoir une valeur.',
    'gt'                   => [
        'array'   => 'Le champ :attribute doit avoir plus de :value éléments.',
        'file'    => 'Le fichier :attribute doit être supérieur à :value kilobytes.',
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
        'string'  => 'Le champ :attribute doit contenir plus de :value caractères.',
    ],
    'gte'                  => [
        'array'   => 'Le champ :attribute doit avoir :value éléments ou plus.',
        'file'    => 'Le fichier :attribute doit être supérieur ou égal à :value kilobytes.',
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :value.',
        'string'  => 'Le champ :attribute doit contenir au moins :value caractères.',
    ],
    'image'                => 'Le champ :attribute doit être une image.',
    'in'                   => 'Le champ :attribute sélectionné est invalide.',
    'in_array'             => 'Le champ :attribute n\'existe pas dans :other.',
    'integer'              => 'Le champ :attribute doit être un entier.',
    'ip'                   => 'Le champ :attribute doit être une adresse IP valide.',
    'ipv4'                 => 'Le champ :attribute doit être une adresse IPv4 valide.',
    'ipv6'                 => 'Le champ :attribute doit être une adresse IPv6 valide.',
    'json'                 => 'Le champ :attribute doit être une chaîne JSON valide.',
    'lt'                   => [
        'array'   => 'Le champ :attribute doit avoir moins de :value éléments.',
        'file'    => 'Le fichier :attribute doit être inférieur à :value kilobytes.',
        'numeric' => 'Le champ :attribute doit être inférieur à :value.',
        'string'  => 'Le champ :attribute doit contenir moins de :value caractères.',
    ],
    'lte'                  => [
        'array'   => 'Le champ :attribute ne doit pas avoir plus de :value éléments.',
        'file'    => 'Le fichier :attribute doit être inférieur ou égal à :value kilobytes.',
        'numeric' => 'Le champ :attribute doit être inférieur ou égal à :value.',
        'string'  => 'Le champ :attribute doit contenir au maximum :value caractères.',
    ],
    'max'                  => [
        'array'   => 'Le champ :attribute ne peut pas avoir plus de :max éléments.',
        'file'    => 'Le fichier :attribute ne peut pas être supérieur à :max kilobytes.',
        'numeric' => 'Le champ :attribute ne peut pas être supérieur à :max.',
        'string'  => 'Le champ :attribute ne peut pas contenir plus de :max caractères.',
    ],
    'mimes'                => 'Le champ :attribute doit être un fichier de type : :values.',
    'mimetypes'            => 'Le champ :attribute doit être un fichier de type : :values.',
    'min'                  => [
        'array'   => 'Le champ :attribute doit avoir au moins :min éléments.',
        'file'    => 'Le fichier :attribute doit être supérieur à :min kilobytes.',
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :min.',
        'string'  => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'not_in'               => 'Le champ :attribute sélectionné est invalide.',
    'not_regex'            => 'Le format du champ :attribute est invalide.',
    'numeric'              => 'Le champ :attribute doit être un nombre.',
    'password'             => 'Le mot de passe est incorrect.',
    'present'              => 'Le champ :attribute doit être présent.',
    'regex'                => 'Le format du champ :attribute est invalide.',
    'required'             => 'Le champ :attribute est obligatoire.',
    'required_if'          => 'Le champ :attribute est obligatoire quand :other vaut :value.',
    'required_unless'      => 'Le champ :attribute est obligatoire sauf si :other est dans :values.',
    'required_with'        => 'Le champ :attribute est obligatoire quand :values est présent.',
    'required_with_all'    => 'Le champ :attribute est obligatoire quand :values sont présents.',
    'required_without'     => 'Le champ :attribute est obligatoire quand :values n\'est pas présent.',
    'required_without_all' => 'Le champ :attribute est obligatoire quand aucun de :values n\'est présent.',
    'same'                 => 'Les champs :attribute et :other doivent être identiques.',
    'size'                 => [
        'array'   => 'Le champ :attribute doit contenir :size éléments.',
        'file'    => 'Le fichier :attribute doit être de :size kilobytes.',
        'numeric' => 'Le champ :attribute doit être de :size.',
        'string'  => 'Le champ :attribute doit contenir :size caractères.',
    ],
    'starts_with'          => 'Le champ :attribute doit commencer par une des valeurs suivantes : :values.',
    'string'               => 'Le champ :attribute doit être une chaîne de caractères.',
    'timezone'             => 'Le champ :attribute doit être un fuseau horaire valide.',
    'unique'               => 'La valeur du champ :attribute est déjà utilisée.',
    'uploaded'             => 'Le téléversement du champ :attribute a échoué.',
    'url'                  => 'Le format du champ :attribute est invalide.',
    'uuid'                 => 'Le champ :attribute doit être un UUID valide.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name'                 => 'nom',
        'email'                => 'email',
        'password'             => 'mot de passe',
        'phone'                => 'téléphone',
        'company'              => 'société',
        'address'              => 'adresse',
        'city'                 => 'ville',
        'zip'                  => 'code postal',
        'country'              => 'pays',
        'price'                => 'prix',
        'description'          => 'description',
        'type'                 => 'type',
        'billing_period'       => 'période de facturation',
        'cyberpanel_host'      => 'hôte CyberPanel',
        'cyberpanel_user'      => 'utilisateur CyberPanel',
        'cyberpanel_password'  => 'mot de passe CyberPanel',
        'ldap_host'            => 'hôte LDAP',
        'ldap_port'            => 'port LDAP',
        'ldap_bind_dn'         => 'DN de bind LDAP',
        'ldap_bind_password'   => 'mot de passe de bind LDAP',
        'ldap_users_dn'        => 'DN des utilisateurs LDAP',
        'ldap_groups_dn'       => 'DN des groupes LDAP',
        'ldap_default_gid'     => 'GID par défaut LDAP',
        'ldap_group'           => 'groupe LDAP',
        'proxmox_host'         => 'hôte Proxmox',
        'proxmox_user'         => 'utilisateur Proxmox',
        'proxmox_password'     => 'mot de passe Proxmox',
        'stripe_secret_key'    => 'clé secrète Stripe',
        'stripe_public_key'    => 'clé publique Stripe',
        'mail_host'            => 'serveur SMTP',
        'mail_port'            => 'port SMTP',
        'mail_username'        => 'utilisateur SMTP',
        'mail_password'        => 'mot de passe SMTP',
        'mail_from_address'    => 'adresse d\'expédition',
        'mail_from_name'       => 'nom d\'expédition',
    ],

];
