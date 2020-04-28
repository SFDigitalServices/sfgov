<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote 
 */


$metadata['https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed'] = array (
    'entityid' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed',
    'certificate' => 'IDCSCertificate.pem',
    'contacts' => 
    array (
    ),
    'metadata-set' => 'saml20-idp-remote',
    'expire' => 1859134892,
    'SingleSignOnService' => 
    array (
      0 => 
      array (
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        'Location' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/idp/sso',
      ),
      1 => 
      array (
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        'Location' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/idp/sso',
      ),
    ),
    'SingleLogoutService' => 
    array (
      0 => 
      array (
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        'Location' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/idp/slo',
        'ResponseLocation' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/idp/slo',
      ),
      1 => 
      array (
        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        'Location' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/idp/slo',
        'ResponseLocation' => 'https://idcs-28d2cb948ab8484aaeb29f302ed52c7d.identity.oraclecloud.com/fed/v1/idp/slo',
      ),
    ),
    'ArtifactResolutionService' => 
    array (
    ),
    'NameIDFormats' => 
    array (
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
  );