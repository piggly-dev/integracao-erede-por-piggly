import axios from "./index";
import qs from 'qs';
import { Settings } from "@/store";

export default {
	getSettings: async () : Promise<Settings> => {
		console.log(`POST pgly_erede_gateway_get_plugin_settings ${window.eRedeSettings.ajax_url}`);

		try
		{
			const { data } = await axios.post(window.eRedeSettings.ajax_url, qs.stringify({
				action: 'pgly_erede_gateway_get_plugin_settings',
				xSecurity: window.eRedeSettings.x_security
			}));

			if ( !data.success )
			{ throw new Error(data.data.message); }

			console.log(data.data);
			return data.data as Settings;
		}
		catch ( err: any )
		{ 
			console.error(err);
			throw err.response.data.data ?? err; 
		}
	},

	saveSettings: async ( section: string, postData: object ) : Promise<boolean> => {
		console.log(`POST pgly_erede_gateway_set_plugin_settings ${window.eRedeSettings.ajax_url}`, postData);

		try
		{
			const { data } = await axios.post(window.eRedeSettings.ajax_url, qs.stringify({
				action: 'pgly_erede_gateway_set_plugin_settings',
				section: section,
				data: JSON.stringify(postData),
				xSecurity: window.eRedeSettings.x_security
			})); 

			if ( !data.success )
			{ throw new Error(data.data.message); }

			return true;
		}
		catch ( err: any )
		{ 
			console.log(err);
			throw err.response.data.data ?? err; 
		}
	}
}