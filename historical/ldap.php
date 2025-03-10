<?php

function create_ldap_connection($ldapUrl = 'ldaps://eds.iam.arizona.edu') {
    // establish LDAP connection
    putenv("LDAPTLS_REQCERT = never");

    $ldap = ldap_connect($ldapUrl);

    if (!$ldap) {
        return "[PHP] Could not connect to LDAP server. Please report this issue if the problem persists.";
    }

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, true);

	$user = 'sunion-mealplans';
	$bindDn = "uid=" . $user . ",ou=App Users,dc=eds,dc=arizona,dc=edu";
    $bindPw = 'CnQc3r2BIDf2DMBRKobragkLIJBsm7';

    // bind as app user
    ldap_bind($ldap, $bindDn, $bindPw);
    return $ldap;
}

function getLdapAttribute($entry, $attribute) {
    if (!array_key_exists($attribute, $entry)) {
        return null;
    }

    $value = $entry[$attribute];
    return $value["count"] == 1 ? $value[0] : array_values(array_filter($value, function($key) {
        return $key != 'count';
    }, ARRAY_FILTER_USE_KEY));
}

function ldap_query($ldap, $searchBase, $searchFilter, $searchAttributes, $cookie='', $size=50) {
    $result = ldap_search(
    $ldap,
    $searchBase,
    $searchFilter,
    $searchAttributes,
    controls: [
        [
            'oid' => LDAP_CONTROL_PAGEDRESULTS,
            'value' => [
                'size' => $size,
                'cookie' => $cookie
            ]
        ]
    ]);

    $errcode = $dn = $errmsg = $refs =  null;

    if (! ldap_parse_result($ldap, $result, $errcode , $dn , $errmsg , $refs, $controls)) {
        throw new \Exception(ldap_error($ldap));
    };
    return ldap_get_entries($ldap, $result);
}

?>
