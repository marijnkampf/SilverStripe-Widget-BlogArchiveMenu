<ul class="BlogArchiveMenu">
	<% control Dates %>
		<li class="$LinkingMode">
			<a href="$Link" class="$LinkingMode">
				<% if NoMonth %>
					$Date.Year
				<% else %>
					$Date.Format(F) $Date.Year $DateFormat
				<% end_if %>
			</a>
			<% if Children %><ul class="level2">
				<% control Children %>
					<li class="$LinkingMode"><a href="$Link" title="{$Title}" class="$LinkingMode">$MenuTitle</a></li>
				<% end_control %>
			</ul><% end_if %>
		</li>
	<% end_control %>
</ul>
