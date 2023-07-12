<?php

	if (!function_exists('isCustomer')) {
		function isCustomer()
		{
			if(!Auth::check()){
				return false;
			}

			return Auth::user()->isCustomer();
		}
	}

	if (!function_exists('isBusiness')) {
		function isBusiness()
		{
			if(!Auth::check()){
				return false;
			}

			return Auth::user()->isBusiness();
		}
	}

	if (!function_exists('isAdmin')) {
		function isAdmin()
		{
			if(!Auth::check()){
				return false;
			}

			return Auth::user()->isAdmin();
		}
	}

	if (!function_exists('isGuest')) {
		function isGuest()
		{
			if(!Auth::check()){
				return true;
			} else {
				return false;
			}
		}
	}