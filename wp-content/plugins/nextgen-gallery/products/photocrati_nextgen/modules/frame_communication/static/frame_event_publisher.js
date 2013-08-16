window.Frame_Event_Publisher = {
	id: window.name,
	cookie_name: 'X-Frame-Events',
	received: [],
	initialized: false,
	children: {},

	is_parent: function(){
		return self.parent.document === self.document;
	},

	is_child: function(){
		return !this.is_parent();
	},

	setup_ajax_handlers: function() {
		var publisher = this;
		jQuery(document).ajaxComplete(function(e, xhr, settings) {
			setTimeout(function() {
				publisher.ajax_handler();
			}, 0);
		});
	},

    ajax_handler: function() {
        this.broadcast(this.get_events(document.cookie));
    },

	initialize: function(){
		this.setup_ajax_handlers();
		if (this.id.length == 0) this.id = "Unknown";
		this.received = this.get_events(document.cookie);
		this.initialized = true;
		if (this.is_parent()) this.emit(this.received, true);
		return this.received;
	},

	register_child: function(child) {
		this.children[child.id] = child;
	},

	broadcast: function(events, child){
		if (!this.initialized) events = this.initialize();
		if (this.is_child()) {
			if (arguments.length <= 1) child = window;
			this.find_parent(child).register_child(child.Frame_Event_Publisher);
			this.notify_parent(events, child);
		}
		else {
			if (arguments.length == 0) events = this.received;
			this.notify_children(events);
		}

	},

	/**
	 * Notifies the parent with a list of events to broadcast
	 */
	notify_parent: function(events, child){
		this.find_parent(child).broadcast(events, child);
	},

	/**
	 * Notifies (broadcasts) to children the list of available events
	 */
	notify_children: function(events){
		this.emit(events);
		for (var index in this.children) {
			var child = this.children[index];
			try {
				child.emit(events);
			}
			catch (ex) {
				if (typeof(console) != "undefined") console.log(ex);
				delete this.children.index;
			}
		}
	},

	/**
	 * Finds the parent window for the current child window
	 */
	find_parent: function(child){
		var retval = child;
		try {
			while (retval.document !== retval.parent.document) retval = retval.parent;
		}
		catch (ex){
			if (typeof(console) != "undefined") console.log(ex);
		}
		return retval.Frame_Event_Publisher;
	},

	/**
	 * Emits all known events to all children
	 */
	emit: function(events, forced){
		if (typeof(forced) == "undefined") forced = false;
		for (var event_id in events) {
			var event = events[event_id];
			if (!forced && !this.has_received_event(event_id)) {
				if (typeof(console) != "undefined") console.log("Emitting "+event_id+":"+event.event+" to "+this.id);
				this.trigger_event(event_id, events[event_id]);
			}
		}
	},

	has_received_event: function(id){
		return this.received[id] != undefined;
	},

	trigger_event: function(id, event){
		var signal = event.context+':'+event.event;
		event.id = id;
		if (typeof(window) != "undefined") jQuery(window).trigger(signal, event);
		this.received[id] = event;
	},

	/**
	 * Parses the events found in the cookie
	 */
	get_events: function(cookie){
		var frame_events = {};
		var cookies = cookie.split(' ');
		try {
			for (var i=0; i<cookies.length; i++) {
				var current_cookie = cookies[i];
				var parts = current_cookie.match(/X-Frame-Events_([^=]+)=(.*)/);
				if (parts) {
					var event_id = parts[1];
					var event_data = parts[2].replace(/;$/, '');
					frame_events[event_id] = JSON.parse(unescape(event_data));
					var cookie_name = 'X-Frame-Events_'+event_id;
					this.delete_cookie(cookie_name);
				}
			}
		}
		catch (Exception) {}
		return frame_events;
	},

	delete_cookie: function(cookie){
		var date = new Date();
		document.cookie = cookie+'=; expires='+date.toGMTString()+';';
	},

	listen_for: function(signal, callback){
		var publisher = this;
		jQuery(window).bind(signal, function(e, event){
			var context = event.context;
			var event_id = event.id;
			if (!publisher.has_received_event(event_id)) {
				callback.call(publisher, event);
				publisher.received[event_id] = event;
			}
		});
	}
}

jQuery(function($){
	Frame_Event_Publisher.broadcast();
});
