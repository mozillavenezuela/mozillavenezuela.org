jQuery(function($){

// Store isn't working 100% for me. So disabling it for now.
store.enabled = false;

window.Ngg_Store = {
    driver: store.enabled ? store : new Persist.Store('ngg_store'),

    get: function(key){
        return this.driver.get(key);
    },

    set: function(key, value){
        if (typeof(value) == 'object') {
            value = JSON.stringify(value);
        }
        return this.driver.set(key, value);
    },

    delete: function(key){
        this.driver.remove(key);
        return !this.has(key);
    },

    has: function(key){
        var value = this.get(key);
        return typeof(value) != 'undefined' && value != null;
    },

    save: function(){
        if (typeof(this.driver['save']) != 'undefined') {
            return this.driver.save();
        }
        else return true;
    }
};

$(window).unload(function(){
    Ngg_Store.save();
})

});
