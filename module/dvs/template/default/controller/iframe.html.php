{if $sBrowser == 'mobile'}
	{template file='dvs.controller.iframe-mobile-view}
{else}
<header>
	<section id="select_new">
		{if $aVideoSelectYears}
			<table width="100%">
				<tr>
					<td>
						<h3>{phrase var='dvs.choose_new_vehicle'}:</h3>
					</td>
					{if isset($aVideoSelectYears.1)}
					<td>
						<ul id="year">
						<li class="init"><span class="init_selected">Select Year</span>
						  <ul>
							{foreach from=$aVideoSelectYears item=iYear}
							<li onclick="$.ajaxCall('dvs.getMakes', 'iYear={$iYear}&amp;sDvsName={$aDvs.title_url}');">
							  {$iYear}
							</li>
							{/foreach}
						  </ul>
						</li>
						</ul>
					</td>
					{/if}
					<td>
						<ul id="makes">
						<li class="init">
						  &nbsp;&nbsp;{phrase var='dvs.select_make'}
						  <ul>
							<li>
							  {phrase var='dvs.please_select_a_year_first'}
							</li>
						  </ul>
						</li>
						</ul>
					</td>
					<td>
						<ul id="models">
						<li class="init">
						  &nbsp;&nbsp;{phrase var='dvs.select_model'}
						  <ul>
							<li>
							  {phrase var='dvs.please_select_a_year_first'}
							</li>
						  </ul>
						</li>
						</ul>
					</td>
				</tr>
			</table>
		{/if}
	</section>
</header>
<article>
	<section id="player">
		{template file='dvs.controller.player.iframe-player}
	</section>

    <aside>
        <div id="contact_box">
            <h2>Contact {$aDvs.dealer_name}</h2>
            {template file='dvs.block.contact-iframe}
        </div>
    </aside>
	
	<section id="dealer_links">
	  <table>
	  <tr>
	  <td>
	  {if $aDvs.inventory_url}
	  <a href="{$aDvs.inventory_url}" onclick="menuInventory('Call To Action Menu Clicks');" rel="nofollow" target="_parent">
		{phrase var='dvs.cta_inventory'}
	  </a>
	  {/if}
	  </td>
	  <td>&nbsp;</td>
	  <td>
	  {if $aDvs.specials_url}
	  <a href="{$aDvs.specials_url}" onclick="menuOffers('Call To Action Menu Clicks');" rel="nofollow" target="_parent">
		{phrase var='dvs.cta_specials'}
	  </a>
	  {/if}
	  </td>
	  </tr>
	  </table>
	</section>


    <section id="video_information">
        <h3 id="video_name">
            <a id="current_video_link" href="{$sNewParentUrl}" onclick="return false;">
                {$aDvs.phrase_overrides.override_video_name_display}
            </a>
        </h3>
        <p class="model_description" id="car_description">{$aDvs.phrase_overrides.override_video_description_display}</p>
    </section>

    <section id="share_links">
        <input type="hidden" value="{$sNewParentUrl}" id="parent_url">
        <input type="hidden" value="{$sVideoUrl}" id="video_url">
        <input type="hidden" value="{phrase var='dvs.twitter_default_share_text' video_year=$aDvs.featured_year video_make=$aDvs.featured_make video_model=$aDvs.featured_model dvs_dealer_name=$aDvs.dealer_name}" id="share_title">
        <input type="hidden" value="{$sVideoThumb}" id="video_thumbnail">

        <table cellpadding="4" cellspacing="4" border="0">
            <tr>
                <td>
                    <a href="#" onclick="tb_show('{phrase var='dvs.share_via_email'}', $.ajaxBox('dvs.emailFormIframe', 'height=400&amp;width=360&amp;sParentUrl=' + encodeURIComponent($('#parent_url').val().replace('WTVDVS_VIDEO_TEMP', $('#video_url').val())) + '&amp;longurl=1&amp;iDvsId={$iDvsId}&amp;sRefId=' + aCurrentVideoMetaData.referenceId));  showEmailShare(); return false;">
                        <img src="{$sImagePath}email-share.png" alt="Share Via Email"/>
                    </a>
                </td>
                {if Phpfox::isModule('redirect')}
               <td>
                    <a href="#" onclick="window.open('https://www.facebook.com/share.php?u=' + encodeURI('{url link='share.'$sDvsRequest}parent_{$sParentUrlEncode}/video_' + $('#video_url').val()), 'Facebook Share', 'width=626,height=436'); facebookShareClick('Share Links'); return false;">
                        <img src="{$sImagePath}facebook-share.png" alt="Share to Facebook"/>
                    </a>
                </td>
                {/if}
                <td>
					<span id="twitter_button_wrapper">
					<a href="https://twitter.com/intent/tweet?text={phrase var='dvs.twitter_default_share_text' video_year=$aDvs.featured_year video_make=$aDvs.featured_make video_model=$aDvs.featured_model dvs_dealer_name=$aDvs.dealer_name}&url={$sParentUrl}" id="twitter_share"><img src="{$sImagePath}twitter-button.png" alt="Tweet" /></a>
					</span>
                </td>
                {if Phpfox::isModule('redirect')}
                <td>
                    <a href="#" onclick="window.open('https://plus.google.com/share?url=' + encodeURI('{url link='share.'$sDvsRequest}parent_{$sParentUrlEncode}/video_' + $('#video_url').val())); googleShareClick('Share Links'); return false;">
                        <img src="{$sImagePath}google-share.png" alt="Google+" title="Google+"/>
                    </a>
                </td>
                {/if}
            </tr>
        </table>
    </section>

</article>
<footer></footer>
{/if}

{literal}
<script type="text/javascript">
    $Behavior.loadUrlVideo = function() {
        setInterval(function() {
            if (!bUpdatedShareUrl) {
                $.ajaxCall('dvs.updateShareUrl', 'ref-id='+ aCurrentVideoMetaData.referenceId + '&iDvsId=' + iDvsId);
                bUpdatedShareUrl = true;
            }
        }, 1000);
    }
</script>
{/literal}