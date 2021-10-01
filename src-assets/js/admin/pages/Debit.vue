<template>
	<h1 class="pgly-wps--title">Cartão de Débito</h1>

	<pgly-row v-if="can_enable">
		<pgly-column>
			<pgly-basic-checkbox
				id="enabled"
				label="Ativar o método de pagamento"
				placeholder="Habilite o método de pagamento via Cartão de Débito"
				:error="fields.enabled.error"
				v-model="fields.enabled.value"
				@afterChange="onChanged">
			</pgly-basic-checkbox>
		</pgly-column>
	</pgly-row>
	<pgly-notification v-else color="danger">
		Antes de habilitar o método de pagamento, não se esqueça
		de preencher o PV e o Token na aba "Principal".
	</pgly-notification>
	
	<pgly-notification color="warning">
		A função débito utiliza a autenticação 3DS. Esta função de segurança
		precisa ser autorizada para a sua conta da e-Rede, saiba mais <a href="https://www.userede.com.br/desenvolvedores/pt/produto/e-Rede#documentacao-3ds" target="_blank">
		clicando aqui</a>.
	</pgly-notification>

	<pgly-row>
		<pgly-column>
			<pgly-basic-input
				id="title"
				label="Título do Método de Pagamento"
				placeholder="Preencha com o título do método..."
				:required="true"
				:error="fields.title.error"
				v-model="fields.title.value">
				<template v-slot:description>
					Informe o título do método de pagamento, conforme
					aparecerá no carrinho de compras.
				</template>
			</pgly-basic-input>
		</pgly-column>
	</pgly-row>

	<pgly-row>
		<pgly-column :size="6">
			<pgly-basic-select
				id="waiting_status"
				label="Status para 'Aguardando Captura'"
				placeholder="Selecione um status..."
				:options="fields.waiting_status.options"
				:error="fields.waiting_status.error"
				v-model="fields.waiting_status.value"
				@afterChange="onChanged">
				<template v-slot:description>
					Recomendamos o status Aguardando Pagamento (<code>on-hold</code>).
					Este status é utilizado quando o pagamento foi autorizado e o limite
					da conta foi retido, entretanto, o pagamento ainda não foi capturado.
				</template>
			</pgly-basic-select>
		</pgly-column>
		<pgly-column :size="6">
			<pgly-basic-select
				id="paid_status"
				label="Status para 'Pagamento Concluído'"
				placeholder="Selecione um status..."
				:options="fields.paid_status.options"
				:error="fields.paid_status.error"
				v-model="fields.paid_status.value"
				@afterChange="onChanged">
				<template v-slot:description>
					Recomendamos o status Processando (<code>processing</code>).
					Este status é utilizado quando o pagamento foi capturado com
					sucesso e dado como confirmado pelo banco operador.
				</template>
			</pgly-basic-select>
		</pgly-column>
	</pgly-row>

	<div class="pgly-wps--space"></div>

	<pgly-async-button 
		label="Salvar Alterações" 
		color="accent"
		:action="submit"
		@buttonLoaded="submitted" 
		@buttonError="notSubmitted"/>
</template>

<script lang="ts">
import { defineComponent } from "@vue/runtime-core";

import store from '@/store';
import api from "@/api/api";

import {
	PglyAsyncButton,
	PglyBasicCheckbox,
	PglyBasicInput,
	PglyBasicSelect,
	PglyNotification,
	PglyRow,
	PglyColumn
} from '@piggly/vue-pgly-wps-settings';

import { IErrorInput, IField } from "@piggly/vue-pgly-wps-settings/dist/types/src/core/interfaces";

export default defineComponent({
	name: 'debit',
	
	components: {
		PglyAsyncButton,
		PglyBasicCheckbox,
		PglyBasicInput,
		PglyBasicSelect,
		PglyNotification,
		PglyRow,
		PglyColumn
	},

	data () {
		return {
			can_enable: store.state.settings.get('global').get('pv', '').length !== 0 && store.state.settings.get('global').get('token', '').length !== 0,
			fields: {
				enabled: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('debit').get('enabled', false),
				},
				title: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('debit').get('title', ''),
				},
				waiting_status: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('debit').get('waiting_status', ''),
					options: store.state.settings.get('runtime').get('statuses')
				},
				paid_status: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('debit').get('paid_status', ''),
					options: store.state.settings.get('runtime').get('statuses')
				}
			} as { [key: string]: IField }
		}
	},

	methods: {
		onChanged () : void {
			if ( !store.state.editing )
			{ store.commit.CHANGE_EDIT_STATE(true); }
		},

		async submit () : Promise<boolean> {
			await api.saveSettings('debit', {
				enabled: this.fields.enabled.value,
				title: this.fields.title.value,
				waiting_status: this.fields.waiting_status.value,
				paid_status: this.fields.paid_status.value
			});

			return true;
		},

		submitted (response: boolean) : void {
			if ( response )
			{
				store.commit.ADD_TOAST({
					body: 'Configurações salvas com sucesso',
					color: 'success',
					timer: 4000
				});

				store.commit.CHANGE_EDIT_STATE(false);
				this.getPluginSettings();
			}
		},

		notSubmitted ( err: Error ) : void {
			store.commit.ADD_TOAST({
				body: err.message || 'Não foi possível salvar as configurações',
				color: 'danger',
				timer: 4000
			});
		},

		async getPluginSettings () : Promise<void> {

			try
			{ store.commit.LOAD_SETTINGS(await api.getSettings()); }
			catch ( err )
			{ 
				console.error(err);

				store.commit.ADD_TOAST({
					timer: 4000,
					body: 'Não foi possível carregar as configurações...',
					color: 'danger'
				});
			}
		}
	}
});
</script>