<template>
	<div class="pgly-wps--space"></div>
	<h1 class="pgly-wps--title">Configurações Gerais</h1>
	
	<pgly-row>
		<pgly-column>
			<pgly-basic-select
				id="env"
				label="Ambiente"
				:options="fields.env.options"
				:error="fields.env.error"
				v-model="fields.env.value">
				<template v-slot:description>
					Indique o ambiente em uso. Em ambiente de produção,
					todas as operações serão processadas na conta da e-Rede.
				</template>
			</pgly-basic-select>
		</pgly-column>
	</pgly-row>

	<pgly-notification v-if="fields.env.value == 'test'" color="danger">
		Você está com o ambiente de testes ativo, cadastre-se no 
		<a href="https://www.userede.com.br/desenvolvedores" target="_blank">
			<strong>Portal dos Desenvolvedores</strong>
		</a>
		para criar um PV e uma Chave de Integração de testes.
	</pgly-notification>

	<pgly-notification v-if="fields.env.value == 'test'" color="warning">
		Para validação	dos cartões de crédito e débito no ambiente de teste, 
		utilize os cartões de testes compartilhados pela e-Rede,
		visualize-os <a href="https://www.userede.com.br/desenvolvedores/pt/produto/e-Rede#tutorial-cartao" target="_blank">
		clicando aqui.</a>
	</pgly-notification>
	
	<pgly-row>
		<pgly-column :size="6">
			<pgly-basic-input
				id="pv"
				label="PV"
				:required="true"
				placeholder="Preencha o PV..."
				:error="fields.pv.error"
				v-model="fields.pv.value"
				@afterChange="onChanged">
				<template v-slot:description>
					Informe o número de filiação, também conhecido como PV.
				</template>
			</pgly-basic-input>
		</pgly-column>
		<pgly-column :size="6">
			<pgly-basic-input
				id="token"
				label="Chave de Integração (Token)"
				:required="true"
				placeholder="Preencha a chave de integração..."
				:error="fields.token.error"
				v-model="fields.token.value"
				@afterChange="onChanged">
				<template v-slot:description>
					Informe a chave de integração, também conhecida como Token.
				</template>
			</pgly-basic-input>
		</pgly-column>
	</pgly-row>

	<pgly-row>
		<pgly-column>
			<pgly-basic-input
				id="soft_descriptor"
				label="Soft Descriptor"
				:required="true"
				placeholder="Preencha um descritor para a cobrança..."
				:error="fields.soft_descriptor.error"
				v-model="fields.soft_descriptor.value"
				max-lenght="12"
				@afterChange="onChanged">
				<template v-slot:description>
					Insira o nome do estabelicimento para identificar
					a cobrança na fatura do cartão.
				</template>
			</pgly-basic-input>
			
			<pgly-notification color="warning">
				Não inclua caracteres especiais ou acentos. Você deve utilizar
				apenas letras e/ou números com, no máximo, 12 caracteres. Antes
				de utilizar o Soft Descriptor, você deverá entrar em contato
				com a e-Rede para liberação.
			</pgly-notification>
		</pgly-column>
	</pgly-row>

	<div class="pgly-wps--space"></div>
	<h2 class="pgly-wps--title pgly-wps-is-6">Pagamento</h2>

	<pgly-row>
		<pgly-column>
			<pgly-basic-checkbox
				id="auto_refund"
				label="Reembolso Automático"
				placeholder="Habilitar o reembolso automático ao marcar o pedido como cancelado ou reembolsado"
				:error="fields.auto_refund.error"
				v-model="fields.auto_refund.value"
				@afterChange="onChanged">
			</pgly-basic-checkbox>
		</pgly-column>
	</pgly-row>
	
	<pgly-row>
		<pgly-column>
			<pgly-basic-select
				id="cron_frequency"
				label="Frequência de Processamento dos Pagamentos"
				:options="fields.cron_frequency.options"
				:error="fields.cron_frequency.error"
				v-model="fields.cron_frequency.value">
				<template v-slot:description>
					Indique a frequência de processamento desejada
					para pagamentos que não puderam ser aprovados
					imediatamente e encontram-se como pendentes.
				</template>
			</pgly-basic-select>
		</pgly-column>
	</pgly-row>

	<div class="pgly-wps--space"></div>
	<h2 class="pgly-wps--title pgly-wps-is-6">Depuração</h2>

	<pgly-row>
		<pgly-column>
			<pgly-basic-checkbox
				id="debug"
				label="Modo Debug"
				placeholder="Habilitar o registro completo de erros, informações e avisos"
				:error="fields.debug.error"
				v-model="fields.debug.value"
				@afterChange="onChanged">
				<template v-slot:description>
					Utilize apenas para inspecionar erros ou processos. 
					Mensagens de log em excesso podem ser criadas quando ativado.
				</template>
			</pgly-basic-checkbox>
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
	PglyLinkButton,
	PglyBasicCheckbox,
	PglyBasicInput,
	PglyBasicSelect,
	PglyNotification,
	PglyRow,
	PglyColumn
} from '@piggly/vue-pgly-wps-settings';

import { IErrorInput, IField } from "@piggly/vue-pgly-wps-settings/dist/types/src/core/interfaces";
import { CronFrequencyOptions } from "@/core/data";

const EnvOptions = [
	{ value: 'test', label: 'Teste' },
	{ value: 'prod', label: 'Produção' }
];

export default defineComponent({
	name: 'global',
	
	components: {
		PglyAsyncButton,
		PglyLinkButton,
		PglyBasicCheckbox,
		PglyBasicInput,
		PglyBasicSelect,
		PglyNotification,
		PglyRow,
		PglyColumn
	},

	data () {
		return {
			window: window,
			fields: {
				debug: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('global').get('debug', false),
				},
				env: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('global').get('env', 'test'),
					options: EnvOptions
				},
				pv: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('global').get('pv', ''),
				},
				token: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('global').get('token', ''),
				},
				soft_descriptor: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('global').get('soft_descriptor', ''),
				},
				auto_refund: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('global').get('auto_refund', false),
				},
				cron_frequency: {
					error: {state: false} as IErrorInput,
					value: store.state.settings.get('global').get('cron_frequency', 'everyfifteen'),
					options: CronFrequencyOptions
				},
			} as { [key: string]: IField }
		}
	},

	methods: {
		onChanged () : void {
			if ( !store.state.editing )
			{ store.commit.CHANGE_EDIT_STATE(true); }
		},

		async submit () : Promise<boolean> {
			if ( this.fields.pv.value.length === 0 )
			{ fieldsSetError(this.fields, 'pv', 'Preencha o PV'); }

			if ( this.fields.token.value.length === 0 )
			{ fieldsSetError(this.fields, 'token', 'Preencha a Chave de Integração'); }
			
			await api.saveSettings('global', {
				debug: this.fields.debug.value,
				env: this.fields.env.value,
				pv: this.fields.pv.value,
				token: this.fields.token.value,
				soft_descriptor: this.fields.soft_descriptor.value,
				auto_refund: this.fields.auto_refund.value,
				cron_frequency: this.fields.cron_frequency.value
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