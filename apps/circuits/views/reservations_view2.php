<h1><?php echo _("Reservation details"); ?></h1>
<div class="float-left">
    <h4>
        <dl>
            <dt><?php echo _("Reservation name"); ?></dt>
            <dd><?php echo $res_name; ?></dd>
            <dt><?php echo _("User"); ?></dt>
            <dd><?php echo @$usr_login; ?></dd>
        </dl>
    </h4>
    <div id="subtab-map" class="tab_subcontent shadow-box">
        <div id="res_mapCanvas" style="width:400px; height:400px;"></div>    
    </div>
    <div id="subtab-points" class="tab_subcontent float-left" style="padding-left:6px;">
        <?= $this->element('view_point', array('app' => 'circuits', 'type' => 'source', 'flow' => $flow)); ?>
        <div id="bandwidth_bar">
            <div id="bandwidth_bar_text">
                <div style="text-align:center;">
                    <label id="lb_bandwidth"><?php echo $bandwidth . " " . _("Mbps") ?></label>
                </div>
            </div>
            <div id="bandwidth_bar_inside"></div>
        </div>
        <?= $this->element('view_point', array('app' => 'circuits', 'type' => 'destination', 'flow' => $flow)); ?>
    </div>
</div>

<div class="float-right" style="padding-left: 4px;">
    <?php if ($gris): ?>

        <form method="POST" action="<?php echo $this->buildLink(array('action' => 'cancel', 'param' => "res_id:$res_id,refresh:1")); ?>">    
            <?php if (!empty($refresh)): ?>
                <div class="controls">
                    <input type="button" class="refresh" value="<?php echo _("Refresh") ?>" onclick="griRefreshStatus(<?php echo $res_id; ?>);" />
                    <input type="submit" class="cancel" disabled="disabled" id="cancel_button" value="<?php echo _("Cancel reservations"); ?>" onclick="return confirm('<?php echo _('Cancel the selected reservations?'); ?>')"/>
                </div>
            <?php endif; ?>
            <?= $this->element('list_gris', compact('gris', 'refresh')+array('app' => 'circuits', 'authorization' => true)); ?>
        </form>
    <?php endif; ?>
    <div id="calendar" class="float-right" style="box-shadow: 2px 2px 4px #888;padding-left: 6px; width:550px;"></div>
</div>
<div style="clear:both;"></div>

<div id="tabs-2" class="tab_content">
    <?= $this->element('view_timer', array('app' => 'circuits', 'timer' => $timer)); ?>
    <?= $request ? $this->element('view_request', compact('request')+array('app' => 'circuits')) : null; ?>

</div>

<div id="tabs-4" class="control_tab">
    <input class="back" type="button" onClick="redir('<?php $action = (!empty($refresh)) ? "status" : "history";
    echo $this->buildLink(array("action" => $action)); ?>');" value="<?php echo _("Back to reservations"); ?>"/>
</div>



<script type="text/javascript" src="<?php echo $this->url(); ?>webroot/js/jquery.weekcalendar.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $this->url(); ?>webroot/css/jquery.weekcalendar.css" />

<link rel='stylesheet' type='text/css' href='http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/smoothness/jquery-ui.css' />
<script type="text/javascript">
    $(function() {
        var $calendar = $('#calendar');
        var id = 10;

        $calendar.weekCalendar({
            buttons: true,
            timeslotsPerHour : 2,
            allowCalEventOverlap : true,
            overlapEventsSeparate: true,
            businessHours : false,/*{start: 8, end: 18, limitDisplay: false },*/
            daysToShow : 7,
            timeslotHeight: 15,
            useShortDayNames: true,
            use24Hour: true,
            height : function($calendar) {
                return 400;
                //return $(window).height() - $("h1").outerHeight() - 1;
            },
            eventAfterRender : function(calEvent, $element) {
                /*if (calEvent.end.getTime() < new Date().getTime()) {
    $event.css("backgroundColor", "#aaa");
    $event.find(".wc-time").css({
       "backgroundColor" : "#999",
       "border" : "1px solid #888"
    });
 }*/
                $element.attr('title', calEvent.title + ": "+
                    calEvent.start.getHours()+":"+calEvent.start.getMinutes()+
                    /*$.datepicker.formatDate('yy-mm-dd', calEvent.start)+*/" - "+
                    calEvent.end.getHours()+":"+calEvent.end.getMinutes()
                    /*$.datepicker.formatDate('yy-mm-dd', calEvent.end)*/); 
                $element.find(".wc-time").empty();
                if (calEvent.status > 0){
                    $element.addClass('authorization-accepted');
                } else if (calEvent.status < 0){
                    $element.addClass('authorization-deny');
                } else {
                    $element.addClass('authorization-waiting');            
                }
            },
            /* draggable : function(calEvent, $event) {
 return calEvent.readOnly != true;
},
resizable : function(calEvent, $event) {
 return calEvent.readOnly != true;
},
eventNew : function(calEvent, $event) {
 var $dialogContent = $("#event_edit_container");
 resetForm($dialogContent);
 var startField = $dialogContent.find("select[name='start']").val(calEvent.start);
 var endField = $dialogContent.find("select[name='end']").val(calEvent.end);
 var titleField = $dialogContent.find("input[name='title']");
 var bodyField = $dialogContent.find("textarea[name='body']");


 $dialogContent.dialog({
    modal: true,
    title: "New Calendar Event",
    close: function() {
       $dialogContent.dialog("destroy");
       $dialogContent.hide();
       $('#calendar').weekCalendar("removeUnsavedEvents");
    },
    buttons: {
       save : function() {
          calEvent.id = id;
          id++;
          calEvent.start = new Date(startField.val());
          calEvent.end = new Date(endField.val());
          calEvent.title = titleField.val();
          calEvent.body = bodyField.val();

          $calendar.weekCalendar("removeUnsavedEvents");
          $calendar.weekCalendar("updateEvent", calEvent);
          $dialogContent.dialog("close");
       },
       cancel : function() {
          $dialogContent.dialog("close");
       }
    }
 }).show();

 $dialogContent.find(".date_holder").text($calendar.weekCalendar("formatDate", calEvent.start));
 setupStartAndEndTimeFields(startField, endField, calEvent, $calendar.weekCalendar("getTimeslotTimes", calEvent.start));

},
eventDrop : function(calEvent, $event) {
},
eventResize : function(calEvent, $event) {
},
eventClick : function(calEvent, $event) {

 if (calEvent.readOnly) {
    return;
 }

 var $dialogContent = $("#event_edit_container");
 resetForm($dialogContent);
 var startField = $dialogContent.find("select[name='start']").val(calEvent.start);
 var endField = $dialogContent.find("select[name='end']").val(calEvent.end);
 var titleField = $dialogContent.find("input[name='title']").val(calEvent.title);
 var bodyField = $dialogContent.find("textarea[name='body']");
 bodyField.val(calEvent.body);

 $dialogContent.dialog({
    modal: true,
    title: "Edit - " + calEvent.title,
    close: function() {
       $dialogContent.dialog("destroy");
       $dialogContent.hide();
       $('#calendar').weekCalendar("removeUnsavedEvents");
    },
    buttons: {
       save : function() {

          calEvent.start = new Date(startField.val());
          calEvent.end = new Date(endField.val());
          calEvent.title = titleField.val();
          calEvent.body = bodyField.val();

          $calendar.weekCalendar("updateEvent", calEvent);
          $dialogContent.dialog("close");
       },
       "delete" : function() {
          $calendar.weekCalendar("removeEvent", calEvent.id);
          $dialogContent.dialog("close");
       },
       cancel : function() {
          $dialogContent.dialog("close");
       }
    }
 }).show();

 var startField = $dialogContent.find("select[name='start']").val(calEvent.start);
 var endField = $dialogContent.find("select[name='end']").val(calEvent.end);
 $dialogContent.find(".date_holder").text($calendar.weekCalendar("formatDate", calEvent.start));
 setupStartAndEndTimeFields(startField, endField, calEvent, $calendar.weekCalendar("getTimeslotTimes", calEvent.start));
 $(window).resize().resize(); //fixes a bug in modal overlay size ??

},*/
            eventMouseover : function(calEvent, $event) {
            },
            eventMouseout : function(calEvent, $event) {
            },
            noEvents : function() {

            },
            data : function(start, end, callback) {
                callback(getEventData());
            }
        });

        function resetForm($dialogContent) {
            $dialogContent.find("input").val("");
            $dialogContent.find("textarea").val("");
        }

        function getEventData() {
            var year = new Date().getFullYear();
            var month = new Date().getMonth();
            var day = new Date().getDate();

            return {
                events : [
                    {
                        "id":1,
                        "start": new Date(year, month, day, 12),
                        "end": new Date(year, month, day, 13, 30),
                        "title":"Reservation1",
                        "status": 1
                    },
                    {
                        "id":2,
                        "start": new Date(year, month, day, 14),
                        "end": new Date(year, month, day, 14, 45),
                        "title":"Reservation2",
                        "class": "test2",
                        "status": -1
                    },
                    {
                        "id":3,
                        "start": new Date(year, month, day + 1, 12),
                        "end": new Date(year, month, day + 1, 17, 45),
                        "title":"Reservation3",
                        "status": 0
                    },
                    {
                        "id":4,
                        "start": new Date(year, month, day - 1, 8),
                        "end": new Date(year, month, day - 1, 9, 30),
                        "title":"Reservation4",
                        "status": -1
                    },
                    {
                        "id":5,
                        "start": new Date(year, month, day + 1, 14),
                        "end": new Date(year, month, day + 1, 15),
                        "title":"Reservation5",
                        "status": 1
                    },
                    {
                        "id":6,
                        "start": new Date(year, month, day + 1, 14),
                        "end": new Date(year, month, day + 1, 15),
                        "title":"Reservation7",
                        "status": 1
                    },
                    {
                        "id":6,
                        "start": new Date(year, month, day, 10),
                        "end": new Date(year, month, day, 11),
                        "title":"Reservation6 (read only)",
                        "status": 0,
                        readOnly : true
                    }

                ]
            };
        }


        /*
         * Sets up the start and end time fields in the calendar event
         * form for editing based on the calendar event being edited
         */
        function setupStartAndEndTimeFields($startTimeField, $endTimeField, calEvent, timeslotTimes) {

            for (var i = 0; i < timeslotTimes.length; i++) {
                var startTime = timeslotTimes[i].start;
                var endTime = timeslotTimes[i].end;
                var startSelected = "";
                if (startTime.getTime() === calEvent.start.getTime()) {
                    startSelected = "selected=\"selected\"";
                }
                var endSelected = "";
                if (endTime.getTime() === calEvent.end.getTime()) {
                    endSelected = "selected=\"selected\"";
                }
                $startTimeField.append("<option value=\"" + startTime + "\" " + startSelected + ">" + timeslotTimes[i].startFormatted + "</option>");
                $endTimeField.append("<option value=\"" + endTime + "\" " + endSelected + ">" + timeslotTimes[i].endFormatted + "</option>");

            }
            $endTimeOptions = $endTimeField.find("option");
            $startTimeField.trigger("change");
        }

        var $endTimeField = $("select[name='end']");
        var $endTimeOptions = $endTimeField.find("option");

        //reduces the end time options to be only after the start time options.
        $("select[name='start']").change(function() {
            var startTime = $(this).find(":selected").val();
            var currentEndTime = $endTimeField.find("option:selected").val();
            $endTimeField.html(
            $endTimeOptions.filter(function() {
                return startTime < $(this).val();
            })
        );

            var endTimeSelected = false;
            $endTimeField.find("option").each(function() {
                if ($(this).val() === currentEndTime) {
                    $(this).attr("selected", "selected");
                    endTimeSelected = true;
                    return false;
                }
            });

            if (!endTimeSelected) {
                //automatically select an end date 2 slots away.
                $endTimeField.find("option:eq(1)").attr("selected", "selected");
            }

        });
    });    
</script>