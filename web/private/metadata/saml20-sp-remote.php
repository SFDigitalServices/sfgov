<?php
/**
 * SAML 2.0 remote SP metadata for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote
 */

/*
 * Example SimpleSAMLphp SAML 2.0 SP
 */
$metadata['https://saml2sp.example.org'] = array(
	'AssertionConsumerService' => 'https://saml2sp.example.org/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp',
	'SingleLogoutService' => 'https://saml2sp.example.org/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp',
);

/*
 * This example shows an example config that works with G Suite (Google Apps) for education.
 * What is important is that you have an attribute in your IdP that maps to the local part of the email address
 * at G Suite. In example, if your Google account is foo.com, and you have a user that has an email john@foo.com, then you
 * must set the simplesaml.nameidattribute to be the name of an attribute that for this user has the value of 'john'.
 */
$metadata['google.com'] = array(
	'AssertionConsumerService' => 'https://www.google.com/a/g.feide.no/acs',
	'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
	'simplesaml.nameidattribute' => 'uid',
	'simplesaml.attributes' => FALSE,
);

$metadata['https://legacy.example.edu'] = array(
	'AssertionConsumerService' => 'https://legacy.example.edu/saml/acs',
        /*
         * Currently, SimpleSAMLphp defaults to the SHA-256 hashing algorithm.
	 * Uncomment the following option to use SHA-1 for signatures directed
	 * at this specific service provider if it does not support SHA-256 yet.
         *
         * WARNING: SHA-1 is disallowed starting January the 1st, 2014.
         * Please refer to the following document for more information:
         * http://csrc.nist.gov/publications/nistpubs/800-131A/sp800-131A.pdf
         */
        //'signature.algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
);

$metadata['https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed'] = array (
	'entityid' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed',
	'contacts' => 
	array (
	),
	'metadata-set' => 'saml20-sp-remote',
	'expire' => 1859134892,
	'AssertionConsumerService' => 
	array (
	  0 => 
	  array (
		'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
		'Location' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/sp/sso',
		'index' => 1,
		'isDefault' => true,
	  ),
	),
	'SingleLogoutService' => 
	array (
	  0 => 
	  array (
		'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
		'Location' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/sp/slo',
		'ResponseLocation' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/sp/slo',
	  ),
	  1 => 
	  array (
		'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
		'Location' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/sp/slo',
		'ResponseLocation' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/sp/slo',
	  ),
	),
	'keys' => 
	array (
	  0 => 
	  array (
		'encryption' => false,
		'signing' => true,
		'type' => 'X509Certificate',
		'X509Certificate' => 'MIIDXzCCAkegAwIBAgIGAWdgtF6zMA0GCSqGSIb3DQEBCwUAMFcxEzARBgoJkiaJ
  k/IsZAEZFgNjb20xFjAUBgoJkiaJk/IsZAEZFgZvcmFjbGUxFTATBgoJkiaJk/Is
  ZAEZFgVjbG91ZDERMA8GA1UEAxMIQ2xvdWQ5Q0EwHhcNMTgxMTI5MTgyMTMyWhcN
  MjgxMTI5MTgyMTMyWjBWMRMwEQYDVQQDEwpzc2xEb21haW5zMQ8wDQYDVQQDEwZD
  bG91ZDkxLjAsBgNVBAMTJWlkY3MtMjhkMmNiOTQ4YWI4NDg0YWFlYjI5ZjMwMmVk
  NTJjN2QwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCI5lP0DBvPby0Z
  HqP52dfCABlUy91SPxnFvFaTTV0kcqSGml1MvSe/ji0MuhGnPTP4Dx3TjFkNN1Or
  cE8cRGuXZLasNvuUrFU4fr66fMkPUx1/IeY4TQAT6XqbNe9yJz+YIGM0KivwAq5p
  zmJ1svXobN7z8Ox/UEn98/073mPG8oIZO7MpUcLoQbe9WnVbW+M91Lg2xFthxkbl
  AYUEEBrEjjwAONwAbqpgm+nGbzpdgxTiKME7t3R5a2eY/qyNl/BEeT/6/F6lx8fo
  JLEPBqOP3UnKX70qK/61Uo2etaaOwMwr9P8yo6h8jOoLAuxOom0gu7ItzZa6PpCO
  GsWD2eIjAgMBAAGjMjAwMA8GA1UdDwEB/wQFAwMH+AAwHQYDVR0OBBYEFDaVZ2bU
  4jH61+gcPI1Q1RZzzINFMA0GCSqGSIb3DQEBCwUAA4IBAQBzLLAjbkhh7VMcMjQg
  bC3TRbtBEtEKPsfUC/0khDm0uLgv2sqUx/b3eVtPM/5tyNB3pfNb++IijSSqPule
  JFLkRh2bhTTyHLxUltSzXndqAMYqJsv9WWjt64TapBzxrchTVPqOy5K8RwO6JaxO
  eU1Bnm/vCa+hDhAPIbx46RX96iMhi+fBsk7QkEea3AcyLyY5wEjw9vE/5EVyEPP/
  7iGNWkWcxZbgyBTCoeQTiIS1+gM32eCOtaC1PIsz9Ffazia1Heb33+dP0ETSH3hh
  h0wLhWBuwk7lfVI30BNQ40q4vF8HOqXH/IvZuQTvj7iKzo5vw4q0h0PPZ8wObyib
  myvk
  ',
	  ),
	  1 => 
	  array (
		'encryption' => true,
		'signing' => false,
		'type' => 'X509Certificate',
		'X509Certificate' => 'MIIDXzCCAkegAwIBAgIGAWdgtF6zMA0GCSqGSIb3DQEBCwUAMFcxEzARBgoJkiaJ
  k/IsZAEZFgNjb20xFjAUBgoJkiaJk/IsZAEZFgZvcmFjbGUxFTATBgoJkiaJk/Is
  ZAEZFgVjbG91ZDERMA8GA1UEAxMIQ2xvdWQ5Q0EwHhcNMTgxMTI5MTgyMTMyWhcN
  MjgxMTI5MTgyMTMyWjBWMRMwEQYDVQQDEwpzc2xEb21haW5zMQ8wDQYDVQQDEwZD
  bG91ZDkxLjAsBgNVBAMTJWlkY3MtMjhkMmNiOTQ4YWI4NDg0YWFlYjI5ZjMwMmVk
  NTJjN2QwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCI5lP0DBvPby0Z
  HqP52dfCABlUy91SPxnFvFaTTV0kcqSGml1MvSe/ji0MuhGnPTP4Dx3TjFkNN1Or
  cE8cRGuXZLasNvuUrFU4fr66fMkPUx1/IeY4TQAT6XqbNe9yJz+YIGM0KivwAq5p
  zmJ1svXobN7z8Ox/UEn98/073mPG8oIZO7MpUcLoQbe9WnVbW+M91Lg2xFthxkbl
  AYUEEBrEjjwAONwAbqpgm+nGbzpdgxTiKME7t3R5a2eY/qyNl/BEeT/6/F6lx8fo
  JLEPBqOP3UnKX70qK/61Uo2etaaOwMwr9P8yo6h8jOoLAuxOom0gu7ItzZa6PpCO
  GsWD2eIjAgMBAAGjMjAwMA8GA1UdDwEB/wQFAwMH+AAwHQYDVR0OBBYEFDaVZ2bU
  4jH61+gcPI1Q1RZzzINFMA0GCSqGSIb3DQEBCwUAA4IBAQBzLLAjbkhh7VMcMjQg
  bC3TRbtBEtEKPsfUC/0khDm0uLgv2sqUx/b3eVtPM/5tyNB3pfNb++IijSSqPule
  JFLkRh2bhTTyHLxUltSzXndqAMYqJsv9WWjt64TapBzxrchTVPqOy5K8RwO6JaxO
  eU1Bnm/vCa+hDhAPIbx46RX96iMhi+fBsk7QkEea3AcyLyY5wEjw9vE/5EVyEPP/
  7iGNWkWcxZbgyBTCoeQTiIS1+gM32eCOtaC1PIsz9Ffazia1Heb33+dP0ETSH3hh
  h0wLhWBuwk7lfVI30BNQ40q4vF8HOqXH/IvZuQTvj7iKzo5vw4q0h0PPZ8wObyib
  myvk
  ',
	  ),
	),
	'validate.authnrequest' => true,
	'saml20.sign.assertion' => true,
  );
