<div class="attendable-date-option <% if $StartDate.InPast %>attendable-date-option--in-past<% end_if %> ">
    <div class="attendable-date-option__icons">
        <i class="far fa-check-circle attendable-date-option__icon attendable-date-option__icon--attending" aria-hidden="true"></i>
        <i class="far fa-circle attendable-date-option__icon attendable-date-option__icon--default" aria-hidden="true"></i>
    </div>
    <div class="attendable-date-option__content">
        <div class="attendable-date-option__dates">
            <% if not $StartDate.InPast %>
                <% loop $DayDateTimes %>
                    <time>
                        $StartDate.Format(EE. d MMM Y)<% if $EndTime && $StartTime && $StartTime != $EndTime %>,
                        {$StartTime.Format('HH:mm')} &ndash; {$EndTime.Format('HH:mm')} uur<% else_if $StartTime != '00:00:00' %>,
                        {$StartTime.Format('HH:mm')} uur<% end_if %>
                    </time>

                    <%-- <% if $StartDate.InPast %>
                    <div class="attendable-date-option__action attendable-date-option__action--placed-available">
                        Deze datum is al geweest.
                    </div>
                    <% end_if %> --%>

                <% end_loop %>
            <% end_if %>
        </div>
        <% if $Location %>
            <% with $Location %>
                <div class="attendable-date-option__location">
                    $Title<% if $Suburb %>, $Suburb<% end_if %>
                </div>
            <% end_with %>
        <% end_if %>
        <div class="attendable-date-option__actions">
            <span class="attendable-date-option__action attendable-date-option__action--placed-available">
                <span class="attendable-date-option__action-icon"><i class="far fa-info-circle"></i></span>
                <% if $StartDate.InPast %>
                    Deze datum is al geweest.
                <% else %>
                    <% if $IsUnlimited %>
                        Nog plaatsen beschikbaar.
                    <% else_if $NumberPlacesAvailable  %>
                        Nog $NumberPlacesAvailable <% if $NumberPlacesAvailable > 1 %>plaatsen<% else %>plaats<% end_if %> beschikbaar.
                    <% else %>
                        Vol, geen plaatsen beschikbaar.
                    <% end_if %>

                    <a href="{$Event.Link}ics/$ID" class="attendable-date-option__action attendable-date-option__action--add-to-calendar" title="Voeg toe aan agenda"><span class="attendable-date-option__action-icon"><i class="far fa-calendar-plus"></i></span> Voeg toe aan agenda</a>

                <% end_if %>
            </span>
            <% if $AttendingMembers %>
                <% loop $AttendingMembers %>
                    <span class="attendable-date-option__action attendable-date-option__action--member">
                        <span>$Title</span>
                        <a href="$Up.getUnattendLink($MemberID)" class="attendable-date-option__action attendable-date-option__action--unattend"><span class="attendable-date-option__action-icon"><i class="far fa-close"></i></span>  Afmelden</a>
                    </span>
                <% end_loop %>
            <% else_if $IsAttending %>
                <a href="$UnattendLink" class="attendable-date-option__action attendable-date-option__action--unattend"><span class="attendable-date-option__action-icon"><i class="far fa-close"></i></span> Afmelden</a>
            <% end_if %>


        </div>
    </div>
</div>