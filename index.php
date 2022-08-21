<?php

#work with amocrm put data to amo
function toAmo($contact_email="sunofhaven@gmail.com", $contact_name="test_namE", $contact_phone="380632064202", $tags="shop.tso.ua", $lead_name="new order_id", $lead_status_id=35275027, $responsible_user_id=29062816, $subdomain='tsocompany'){

		
		/** Получаем access_token из вашего хранилища */
		$access_token = 'loajdoaiwjd1231jd21391283091oij12o';
		/** Формируем заголовки */

		$headers = [
			'Authorization: Bearer ' . $access_token
		];


		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/list?query='.$contact_email;
		$curl=curl_init(); # Save the cURL session handle
		# Set the necessary options for cURL session
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
		curl_setopt($curl,CURLOPT_URL, $link);
		curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl,CURLOPT_HEADER, false);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
	 
		$out=curl_exec($curl); # Initiate a request to the API and stores the response to variable
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$Response=json_decode($out,true);
		#echo '<b>Авторизация:</b>'; echo '<pre>'; print_r($Response); echo '</pre>';

		if ($Response["response"]["contacts"][0]["id"]) {
			$u_id = $Response["response"]["contacts"][0]["id"];
			$last_modified = $Response["response"]["contacts"][0]["last_modified"];
			$linked_leads_id = array();

			if(is_array($Response["response"]["contacts"][0]["linked_leads_id"]))
			foreach($Response["response"]["contacts"][0]["linked_leads_id"] as $x) {
					array_push($linked_leads_id, $x);
				};

			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
			$curl=curl_init(); 
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
			curl_setopt($curl,CURLOPT_URL, $link);
			curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl,CURLOPT_HEADER, false);
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);

			$out=curl_exec($curl); 
			$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
			curl_close($curl);
			$Response=json_decode($out,true);
			$account=$Response['response']['account'];
			$amoAllFields = $account['custom_fields']; 
			$amoConactsFields = $account['custom_fields']['contacts']; 
			#echo '<b>Поля из амо:</b>'; echo '<pre>'; print_r($amoConactsFields); echo '</pre>';

			$sFields = array_flip(array(
					'PHONE', 
					'EMAIL'
				)
			);
			foreach($amoConactsFields as $afield) {
				if(isset($sFields[$afield['code']])) {
					$sFields[$afield['code']] = $afield['id'];
				}
			}

			$leads['request']['leads']['add']=array(
				array(
					'name' => $lead_name,
					'status_id' => $lead_status_id, 
					'responsible_user_id' => $responsible_user_id, 
					//'date_create'=>1298904164, //optional
					//'price'=>300000,
					'tags' => $tags,
				)
			);

			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/set';
			$curl=curl_init(); 
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
			curl_setopt($curl,CURLOPT_URL, $link);
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
			curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($leads));
			curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl,CURLOPT_HEADER, false);
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
			$out=curl_exec($curl); 
			$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
			$Response=json_decode($out,true);

			if(is_array($Response['response']['leads']['add']))
				foreach($Response['response']['leads']['add'] as $lead) {
					$lead_id = $lead["id"];
					array_push($linked_leads_id, $lead_id);
				};

			//print_r($linked_leads_id);

			$contacts['request']['contacts']['update']=array(
				array(
					'id' => $u_id,
					'name'=>$contact_name, # Contact name
					//'last_modified'=>1298904164, //optional
					'last_modified'=>$last_modified,
					'responsible_user_id' => $responsible_user_id, //id ответственного
					'linked_leads_id'=>$linked_leads_id,
					'tags' => $tags,
					//'company_name'=>'amoCRM',
					'custom_fields'=>array(
						array(
							# Phones
							'id'=>$sFields['PHONE'], # A unique identifier of custom field
							'values'=>array(
								array(
									'value'=>$contact_phone,
									'enum'=>'WORK' # Mobile
								),
							)
						),
						array(
							# E-mails
							'id'=>$sFields['EMAIL'],
							'values'=>array(
								array(
									'value'=>$contact_email,
									'enum'=>'WORK',
								),
							)
						),
					)
				)
			);

			# Create a link for request
			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/set';

			$curl=curl_init(); # Save the cURL session handle
			# Set the necessary options for cURL session

			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
			curl_setopt($curl,CURLOPT_URL, $link);
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
			curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($contacts));
			curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl,CURLOPT_HEADER, false);
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);    

			$out=curl_exec($curl); # Initiate a request to the API and stores the response to variable
			$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);

			#echo "renewed contact + new lead";

		}
		else {

			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
			$curl=curl_init(); 
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
			curl_setopt($curl,CURLOPT_URL, $link);
			curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl,CURLOPT_HEADER, false);
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);

			$out=curl_exec($curl); 
			$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
			curl_close($curl);
			$Response=json_decode($out,true);
			$account=$Response['response']['account'];
			$amoAllFields = $account['custom_fields']; 
			$amoConactsFields = $account['custom_fields']['contacts']; 
			#echo '<b>Поля из амо:</b>'; echo '<pre>'; print_r($amoConactsFields); echo '</pre>';

			$sFields = array_flip(array(
					'PHONE', 
					'EMAIL'
				)
			);
			foreach($amoConactsFields as $afield) {
				if(isset($sFields[$afield['code']])) {
					$sFields[$afield['code']] = $afield['id'];
				}
			}
			$leads['request']['leads']['add']=array(
				array(
					'name' => $lead_name,
					'status_id' => $lead_status_id, 
					'responsible_user_id' => $responsible_user_id, 
					//'date_create'=>1298904164, //optional
					//'price'=>300000,
					'tags' => $tags,
				)
			);

			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/set';
			$curl=curl_init(); 
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
			curl_setopt($curl,CURLOPT_URL, $link);
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
			curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($leads));
			curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl,CURLOPT_HEADER, false);
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
			$out=curl_exec($curl); 
			$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
			$Response=json_decode($out,true);

			if(is_array($Response['response']['leads']['add']))
				foreach($Response['response']['leads']['add'] as $lead) {
					$lead_id = $lead["id"];
					array_push($linked_leads_id, $lead_id);
				};
			//print_r($linked_leads_id);

			$contacts['request']['contacts']['add']=array(
				array(
					'name' => $contact_name,
					'linked_leads_id' => array($lead_id), 
					'responsible_user_id' => $responsible_user_id,
					'tags' => $tags,
					//'company_name'=>'amoCRM',
					'custom_fields'=>array(

						array(
							# Phones
							'id'=>$sFields['PHONE'], # A unique identifier of custom field
							'values'=>array(
								array(
									'value'=>$contact_phone,
									'enum'=>'WORK' # Mobile
								),
							)
						),
						array(
							# E-mails
							'id'=>$sFields['EMAIL'],
							'values'=>array(
								array(
									'value'=>$contact_email,
									'enum'=>'WORK',
								),
							)
						),
					)
				)
			);

			# Create a link for request
			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/set';

			$curl=curl_init(); # Save the cURL session handle
			# Set the necessary options for cURL session

			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
			curl_setopt($curl,CURLOPT_URL, $link);
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
			curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($contacts));
			curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl,CURLOPT_HEADER, false);
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);    

			$out=curl_exec($curl); # Initiate a request to the API and stores the response to variable
			$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);

			#echo "new contact and lead added";
		}


		$code = (int)$code;
		$errors = [
			400 => 'Bad request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not found',
			500 => 'Internal server error',
			502 => 'Bad gateway',
			503 => 'Service unavailable',
		];

		try
		{
			/** Если код ответа не успешный - возвращаем сообщение об ошибке  */
			if ($code < 200 || $code > 204) {
				throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
			}
		}
		catch(\Exception $e)
		{
			die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
		}
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////////
			$subdomain = 'tsocompany';

			$lead_name = "new order #".$data['order_id'];
			$lead_status_id = 35275027;
			$responsible_user_id = 29062816;
			$tags = "shop.tso.ua";

			$contact_name = $data['firstname']." ".$data['lastname'];
			$contact_phone = $data['telephone'];
			$contact_email = $data['email'];

			toAmo($contact_email, $contact_name, $contact_phone, $tags, $lead_name, $lead_status_id, $responsible_user_id, $subdomain);
//////////////////////////////////////////////////////////////////////////////////////////////////////


?>