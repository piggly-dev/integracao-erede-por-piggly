<template>
	<h1 class="pgly-wps--title">Cartão de Crédito</h1>

	<pgly-row v-if="can_enable">
		<pgly-column>
			<pgly-basic-checkbox
				id="enabled"
				label="Ativar o método de pagamento"
				placeholder="Habilite o método de pagamento via Cartão de Crédito"
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
					do cartão foi retido, entretanto, o pagamento ainda não foi capturado.
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
					sucesso e dado como confirmado pela operadora do cartão.
				</template>
			</pgly-basic-select>
		</pgly-column>
	</pgly-row>	

	<pgly-row>
		<pgly-column>
			<pgly-basic-checkbox
				id="auto_capture"
				label="Captura Automática"
				placeholder="Habilite para capturar o valor da compra imediatamente após a autorização de crédito"
				:error="fields.auto_capture.error"
				v-model="fields.auto_capture.value"
				@afterChange="onChanged">
			</pgly-basic-checkbox>
		</pgly-column>
	</pgly-row>

	<pgly-notification color="danger">
		A captura automática não é indicada em operações de risco
		com alto valor de compra, o ideal entre a autorização e 
		captura da compra é ter um	serviço de intermediação de 
		risco como a ClearSale ou afins.
	</pgly-notification>

	<div class="pgly-wps--space"></div>
	<h2 class="pgly-wps--title pgly-wps-is-6">Condições de Parcelamento</h2>

	<pgly-row>
		<pgly-column :size="6">
			<pgly-basic-input
				id="min_parcels_value"
				type="number"
				label="Valor Mínimo para Parcelas"
				tag="R$"
				placeholder="Preencha com o valor mínimo..."
				:error="fields.min_parcels_value.error"
				v-model="fields.min_parcels_value.value">
				<template v-slot:description>
					Informe o valor mínimo da parcela, abaixo desse
					valor não serão calculadas parcelas para o pedido.
				</template>
			</pgly-basic-input>
		</pgly-column>
		<pgly-column :size="6">
			<pgly-basic-input
				id="max_parcels_number"
				type="number"
				label="Quantidade Máxima de Parcelas"
				tag="VEZ(ES)"
				placeholder="Preencha a quantidade máxima..."
				:error="fields.max_parcels_number.error"
				v-model="fields.max_parcels_number.value">
				<template v-slot:description>
					Informe a quantidade máxima de parcelas permitidas
					para as operações de compra.
				</template>
			</pgly-basic-input>
		</pgly-column>
	</pgly-row>

	<div class="pgly-wps--space"></div>
	<h2 class="pgly-wps--title pgly-wps-is-6">Rede de Parceiros</h2>

	<pgly-row>
		<pgly-column :size="6">
			<pgly-basic-input
				id="partner_module"
				label="ID da Rede de Parceiros"
				placeholder="Preencha o ID da rede de parceiros..."
				:error="fields.partner_module.error"
				v-model="fields.partner_module.value">
				<template v-slot:description>
					Preencha somente quando aplicável e quando solicitado
					pela e-Rede.
				</template>
			</pgly-basic-input>
		</pgly-column>
		<pgly-column :size="6">
			<pgly-basic-input
				id="partner_gateway"
				label="Gateway da Rede de Parceiros"
				placeholder="Preencha o gateway da rede de parceiros..."
				:error="fields.partner_gateway.error"
				v-model="fields.partner_gateway.value">
				<template v-slot:description>
					Preencha somente quando aplicável e quando solicitado
					pela e-Rede.
				</template>
			</pgly-basic-input>
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
import { fieldsSetError } from '@/core/global';

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
	name: 'credit',
	
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
					value: store.state.settings.get('credit').get('enabled', false),
				},
				title: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('credit').get('title', ''),
				},
				waiting_status: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('credit').get('waiting_status', ''),
					options: store.state.settings.get('runtime').get('statuses')
				},
				paid_status: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('credit').get('paid_status', ''),
					options: store.state.settings.get('runtime').get('statuses')
				},
				auto_capture: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('credit').get('auto_capture', false),
				},
				min_parcels_value: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('credit').get('min_parcels_value', 0).toString()
				},
				max_parcels_number: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('credit').get('max_parcels_number', 0).toString()
				},
				partner_module: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('credit').get('partner_module', ''),
				},
				partner_gateway: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('credit').get('partner_gateway', ''),
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
			await api.saveSettings('credit', {
				enabled: this.fields.enabled.value,
				title: this.fields.title.value,
				waiting_status: this.fields.waiting_status.value,
				paid_status: this.fields.paid_status.value,
				auto_capture: this.fields.auto_capture.value,
				min_parcels_value: this.fields.min_parcels_value.value,
				max_parcels_number: this.fields.max_parcels_number.value,
				partner_module: this.fields.partner_module.value,
				partner_gateway: this.fields.partner_gateway.value
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