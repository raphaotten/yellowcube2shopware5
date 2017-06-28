{assign var="shopdata" value=$smarty.session.Shopware.shopData}
{foreach from=$Pages item=postions name="pagingLoop" key=page}
	<div id="head_logo">
		{$Containers.Logo.value}
	</div>
	<div id="header">
		<div id="head_left">
		{if $smarty.foreach.pagingLoop.first}
			{block name="document_index_selectAdress"}
				{assign var="address" value="billing"}
			{/block}
			<div id="head_sender">
				<p class="sender">{$Containers.Header_Sender.value}</p>
				{$User.$address.company}<br />
				{$User.$address.salutation|salutation}
				{if {config name="displayprofiletitle"}}
					{$User.$address.title}<br/>
				{/if}
				{$User.$address.firstname} {$User.$address.lastname}<br />
				{$User.$address.street}<br />
				{block name="document_index_address_additionalAddressLines"}
					{if {config name=showAdditionAddressLine1}}
						{$User.$address.additional_address_line1}<br />
					{/if}
					{if {config name=showAdditionAddressLine2}}
						{$User.$address.additional_address_line2}<br />
					{/if}
				{/block}
				{block name="document_index_address_cityZip"}
                    {if {config name=showZipBeforeCity}}
                        {$User.$address.zipcode} {$User.$address.city}<br />
                    {else}
                        {$User.$address.city} {$User.$address.zipcode}<br />
                    {/if}
				{/block}
				{block name="document_index_address_countryData"}
				    {if $User.$address.state.shortcode}{$User.$address.state.shortcode} - {/if}{$User.$address.country.countryen}<br />
				{/block}
			</div>
		{/if}
		</div>
		<div id="head_right">
				<strong>
				{block name="document_index_head_right"}
					{$Containers.Header_Box_Right.value}
					{s name="DocumentIndexCustomerID"}{/s} {$User.billing.customernumber|string_format:"%06d"}<br />
					{if $User.billing.ustid}
					{s name="DocumentIndexUstID"}{/s} {$User.billing.ustid|replace:" ":""|replace:"-":""}<br />
					{/if}
					{s name="DocumentIndexOrderID"}{/s} {$Order._order.ordernumber}<br />
					{s name="DocumentIndexDate"}{/s} {$Document.date}<br />
					{if $Document.deliveryDate}{s name="DocumentIndexDeliveryDate"}{/s} {$Document.deliveryDate}<br />{/if}
				{/block}
				</strong>
		</div>
	</div>
	
	<div id="head_bottom" style="clear:both">
		{block name="document_index_head_bottom"}
			<h1>{s name="DocumentIndexInvoiceNumber"}Rechnung Nr. {$Document.id}{/s}</h1>
			{s name="DocumentIndexPageCounter"}Seite {$page+1} von {$Pages|@count}{/s}
		 {/block}
	</div>

	<div id="content" {if $postions|@count < 3}style="height:50mm"{/if}>
		<table cellpadding="0" cellspacing="0" width="100%">
			<tbody valign="top">
				<tr>
					{block name="document_index_table_head_pos"}
						<td align="left" width="5%" class="head">
							<strong>{s name="DocumentIndexHeadPosition"}Pos.{/s}</strong>
						</td>
					{/block}
					{block name="document_index_table_head_nr"}
						<td align="left" width="10%" class="head">
							<strong>{s name="DocumentIndexHeadArticleID"}Art-Nr.{/s}</strong>
						</td>
					{/block}
					{block name="document_index_table_head_name"}
						<td align="left" width="48%" class="head">
							<strong>{s name="DocumentIndexHeadName"}Bezeichnung{/s}</strong>
						</td>
					{/block}
					{block name="document_index_table_head_quantity"}
						<td align="right" width="5%" class="head">
							<strong>{s name="DocumentIndexHeadQuantity"}Anz.{/s}</strong>
						</td>
					{/block}
					{block name="document_index_table_head_tax"}
						{if $Document.netto != true}
							<td align="right" width="6%" class="head">
								<strong>{s name="DocumentIndexHeadTax"}MwSt.{/s}</strong>
							</td>
						{/if}
					{/block}
					{block name="document_index_table_head_price"}
						{if $Document.netto != true && $Document.nettoPositions != true}
						    <td align="right" width="10%" class="head">
								<strong>{s name="DocumentIndexHeadPrice"}Brutto Preis{/s}</strong>
							</td>
						    <td align="right" width="12%" class="head">
								<strong>{s name="DocumentIndexHeadAmount"}Brutto Gesamt{/s}</strong>
							</td>
						{else}
							 <td align="right" width="10%" class="head">
								<strong>{s name="DocumentIndexHeadNet"}Netto Preis{/s}</strong>
							 </td>
						     <td align="right" width="12%" class="head">
								<strong>{s name="DocumentIndexHeadNetAmount"}Netto Gesamt{/s}</strong>
							 </td>
						{/if}
					{/block}
				</tr>
				{foreach from=$postions item=position key=number}
					{block name="document_index_table_each"}
						<tr>
							{block name="document_index_table_pos"}
								<td align="left" width="5%" valign="top">
									{$number+1}
								</td>
							{/block}
							{block name="document_index_table_nr"}
								<td align="left" width="10%" valign="top">
									{$position.articleordernumber|truncate:14:""}
								</td>
							{/block}
							{block name="document_index_table_name"}
								<td align="left" width="48%" valign="top">
									{if $position.name == 'Versandkosten'}
										{s name="DocumentIndexPositionNameShippingCosts"}{$position.name}{/s}
									{else}
										{s name="DocumentIndexPositionNameDefault"}{$position.name|nl2br}{/s}
									{/if}

									{if $isForeignCountry}
										<br />
										<strong>{s name=yellowcube/details/articles/form/tariff}Commodity Code{/s}</strong>: {$position.tariff}<br />
										<strong>{s name=yellowcube/details/articles/form/tara}Tara{/s}</strong>: {$position.tara} Kg<br />
										<strong>{s name=yellowcube/details/articles/form/origin}Country of Origin{/s}</strong>: {$position.origin}<br />
									{/if}
								</td>
							{/block}
							{block name="document_index_table_quantity"}
								<td align="right" width="5%" valign="top">
									{$position.quantity}
								</td>
							{/block}
							{block name="document_index_table_tax"}
								{if $Document.netto != true}
									<td align="right" width="6%" valign="top">
										{$position.tax} %
									</td>
								{/if}
							{/block}
							{block name="document_index_table_price"}
								{if $Document.netto != true && $Document.nettoPositions != true}
								    <td align="right" width="10%" valign="top">
										{$position.price|currency}
									</td>
								    <td align="right" width="12%" valign="top">
								    	{$position.amount|currency}
									</td>
								{else}
									<td align="right" width="10%" valign="top">
										{$position.netto|currency}
									</td>
								    <td align="right" width="12%" valign="top">
								    	{$position.amount_netto|currency}
									</td>
								{/if}
							{/block}
						</tr>
					{/block}
				{/foreach}
			</tbody>
		</table>
	</div>

	{if $smarty.foreach.pagingLoop.last}
		{block name="document_index_amount"}
		 	<div id="amount">
			  <table width="300px" cellpadding="0" cellspacing="0">
			  <tbody>
			  <tr>
			  	<td align="right" width="100px" class="head">{s name="DocumentIndexTotalNet"}Gesamtkosten Netto:{/s}</td>
			  	<td align="right" width="200px" class="head">{$Order._amountNetto|currency}</td>
			  </tr>
			  {if $Document.netto == false}
				  {foreach from=$Order._tax key=key item=tax}
				  <tr>
				  	<td align="right">{s name="DocumentIndexTax"}zzgl. {$key} % MwSt:{/s}</td>
				  	<td align="right">{$tax|currency}</td>
				  </tr>
				  {/foreach}
			  {/if}
			  {if $Document.netto == false}
				  <tr>
				    <td align="right"><b>{s name="DocumentIndexTotal"}Gesamtkosten:{/s}</b></td>
				    <td align="right"><b>{$Order._amount|currency}</b></td>
				  </tr>
			  {else}
			 	  <tr>
				    <td align="right"><b>{s name="DocumentIndexTotal"}Gesamtkosten:{/s}</b></td>
				    <td align="right"><b>{$Order._amountNetto|currency}</b></td>
				  </tr>
			  {/if}
			  </tbody>
			  </table>
			</div>
		{/block}
		{block name="document_index_info"}
			<div id="info">
			{block name="document_index_info_comment"}
				{if $Document.comment}
					<div style="font-size:11px;color:#333;font-weight:bold">
						{$Document.comment|replace:"€":"&euro;"}
					</div>
				{/if}
			{/block}
			{block name="document_index_info_net"}
				{if $Document.netto == true}
				<p>{s name="DocumentIndexAdviceNet"}Hinweis: Der Empfänger der Leistung schuldet die Steuer.{/s}</p>
				{/if}
				<div>{s name="DocumentIndexSelectedPayment"}Gew&auml;hlte Zahlungsart{/s} {$Order._payment.description}</div>
			{/block}
			{block name="document_index_info_voucher"}
				{if $Document.voucher}
				  	<div style="font-size:11px;color:#333;">
				  	{s name="DocumentIndexVoucher"}
						Für den nächsten Einkauf schenken wir Ihnen einen {$Document.voucher.value} {$Document.voucher.prefix} Gutschein
						mit dem Code "{$Document.voucher.code}".<br />
					{/s}
					</div>
				{/if}
			{/block}
			{block name="document_index_info_ordercomment"}
				{if $Order._order.customercomment}
					<div style="font-size:11px;color:#333;">
						{s name="DocumentIndexComment"}Kommentar:{/s}
						{$Order._order.customercomment|replace:"€":"&euro;"}
					</div>
				{/if}
			{/block}
			{block name="document_index_info_dispatch"}
				{if $Order._dispatch.name}
					<div style="font-size:11px;">
						{s name="DocumentIndexSelectedDispatch"}Gewählte Versandart:{/s}
						{$Order._dispatch.name}
					</div>
				{/if}
			{/block}

				{$Containers.Content_Info.value}
			{block name="document_index_info_currency"}
				{if $Order._currency.factor > 1}{s name="DocumentIndexCurrency"}
					<br>Euro Umrechnungsfaktor: {$Order._currency.factor|replace:".":","}
					{/s}
				{/if}
			{/block}
			</div>
			<div>
			{*<img src="{$smarty.session.Shopware.pluginPath|cat:'owner_signature.jpg'}" class="signpic" />
			<br/>
			Raphael Otten<br/>*}
			<br/>Ihr SIHAWO-Team
			</div><br/>
		{/block}
	{/if}


	<div id="footer">
	{$Containers.Footer.value}
	</div>
	{if !$smarty.foreach.pagingLoop.last}
		<pagebreak />
	{/if}
{/foreach}
