<?php

class Ld_Services_Identica extends Ld_Services_Twitter
{

    protected $_serviceName = 'identica';

    protected $_serviceHost = 'identi.ca';

    protected $_baseApiUrl = 'https://identi.ca/api';

    protected $_authorizeUrl = 'https://identi.ca/api/oauth/authorize';

    protected $_requestTokenUrl = 'https://identi.ca/api/oauth/request_token';

    protected $_accessTokenUrl = 'https://identi.ca/api/oauth/access_token';

}
