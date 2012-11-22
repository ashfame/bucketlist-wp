<html>
	<head>
		<title>Backbone.js sample app</title>
		<style>
			#bucketlist-items {
				margin: 20px;
				list-style: none;
				padding: 0;
			}
			#bucketlist-items li div {
				padding: 19px 19px 19px 70px;
			}
			#bucketlist-items li a {
				cursor: pointer;
			}
			.incomplete {
				background-color: blueViolet;
				color: #FFF;
			}
			.complete {
				background-color: green;
				color: #FFF;
			}
		</style>
	</head>
	<body>
		<div id="playground">
			<header><h1>Bucketlista baby!</h1></header>
			<section>
				<label for="new-bucketlist">Add to bucketlist</label>
				<input type="text" id="new-bucketlist" />
				<input type="submit" id="add-bucketlist" value="add" />
				<ul id="bucketlist-items"></ul>
			</section>
			<footer></footer>
		</div>

		<script type="text/template" id="bucketlist-template">
			<div class="<%= status %>">
				<%= title %> (<a class="checkoff">checkoff</a>) | (<a class="clear">clear</a>)
			</div>
		</script>

		<script type="text/template" id="stats-template">
			<span id="remaining-wishes-count"><strong><%= remaining %></strong> <%= remaining === 1 ? 'wish' : 'wishes' %> left</span>
			<span id="done-wishes-count"><strong><%= done %></strong> <%= done === 1 ? 'wish' : 'wishes' %> done</span>
			<span id="all-wishes-count"><strong><%= all %></strong> <%= all === 1 ? 'wish' : 'wishes' %> total</span>
		</script>

		<script src="<?php bloginfo( 'template_directory' ); ?>/js/jquery.min.js"></script>
		<script src="<?php bloginfo( 'template_directory' ); ?>/js/underscore-min.js"></script>
		<script src="<?php bloginfo( 'template_directory' ); ?>/js/backbone-min.js"></script>
		<script>

			/**
			 * Custom Backbone Sync method
			 */

			 var app;

			(function($) {
				Backbone.sync = function(method, model, options) {

					var params = _.extend({
						type: 'POST',
						dataType: 'json',
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						contentType: 'application/x-www-form-urlencoded;charset=UTF-8'
					}, options);

					if (method == 'read') {
						params.type = 'GET';
						params.data = model.id
					}

					if (!params.data && model && (method == 'create' || method == 'update' || method == 'delete')) {
						params.data = JSON.stringify(model.toJSON());
					}


					if (params.type !== 'GET') {
						params.processData = false;
					}

					params.data = $.param({
						action: 'backbone',
						backbone_method: method,
						//backbone_model:z model.dbModel,
						backbone_model: 'bucketlistitem',
						content: params.data
					});

					// Make the request.
					return $.ajax(params);

				};

			})(jQuery);

			/**
			 * Bucketlist Model
			 */

			var wish = Backbone.Model.extend({

				defaults: {
					title: '',
					status: 'incomplete'
				},

				validate: function(attributes){
					if ( attributes.title == '' ) {
						console.log('title dafuq');
						return "Bucketlist entry with no title means you don't wanna do anything. Is that true?";
					}
				},

				initialize: function(){

					console.log('initializing wish model');

					/* Add listeners here, when needed */

					this.on('change:status',function(){
						console.log('Bucketlist\'s status changed');
					});

					this.on('change',function(){
						this.save();
					});

					// Alert the error
					this.on('error',function(model,error){
						console.log(model,error);
					});
				},

				toggle: function(){
					this.set({
						'status': this.get('status') == 'incomplete' ? 'complete' : 'incomplete'
					});
				},

				clear: function(){
					console.log('inside model\'s clear func',this);
					this.destroy();
				}

			});

			/*********************************************************************/

			/**
			 * BucketList View
			 */

			var wishView = Backbone.View.extend({

				tagName: 'li',

				template: _.template($('#bucketlist-template').html()),

				events: {
					'click .checkoff': 'toggle',
					'click .clear': 'clear'
				},

				initialize: function(){
					this.model.bind('change',this.render,this);
					this.model.bind('add',this.render,this);
					this.model.bind('destroy',this.remove,this);
				},

				toggle: function(){
					this.model.toggle();
				},

				clear: function(){
					console.log('inside view\'s clear func',this);
					// pass the call to clear function of model instead of calling the destroy function directly as this gives us the opportunity to do more things when destroying it
					this.model.clear();
				},

				render: function(){
					console.log('rendering',this.model.toJSON());
					this.$el.html(this.template(this.model.toJSON()));
					return this; // supports chained calls
				}

			});

			/*********************************************************************/

			/**
			 * BucketList Collection
			 */

			var BucketListCollection = Backbone.Collection.extend({

				model: wish,

				done: function(){
					return this.filter(function(wish){ return wish.get('status') == 'complete' });
				},

				remaining: function(){
					return this.without.apply(this, this.done());
				}

			});

			var wishes = new BucketListCollection();

			/*********************************************************************/

			/**
			 * Bucketlist top level View
			 */

			var Appview = Backbone.View.extend({

				el: $('#playground'),

				statsTemplate: _.template( $('#stats-template').html() ),

				events: {
					'click #add-bucketlist': 'create',
					'click #remaining-wishes-count': 'remainingWishesRoute',
					'click #done-wishes-count': 'doneWishesRoute',
					'click #all-wishes-count': 'allWishesRoute'
				},

				initialize: function(){

					this.input = $('#new-bucketlist');
					this.footer = $('#playground footer');
					this.items = $('#bucketlist-items');

					wishes.bind('add',this.addOne,this);
					wishes.bind('reset',this.addAll,this);
					wishes.bind('all',this.render,this);

					wishes.fetch();
				},

				render: function(){
					var done = wishes.done().length;
					var remaining = wishes.remaining().length;

					this.footer.html(this.statsTemplate({
						done: done,
						remaining: remaining,
						all: done + remaining
					}));
				},

				addOne: function(wish){
					var view = new wishView({
						model: wish
					});

					this.$("#bucketlist-items").append(view.render().el);
				},

				addAll: function(){
					wishes.each(this.addOne);
				},

				create: function(){
					wishes.create({ title: this.input.val() });
					this.input.val('');
				},

				remainingWishesRoute: function(){
					wishRouter.navigate('remaining',{trigger: true});
					this.items.find('.incomplete').parents('li').show();
					this.items.find('.complete').parents('li').hide();
				},

				doneWishesRoute: function(){
					wishRouter.navigate('done',{trigger: true});
					this.items.find('.complete').parents('li').show();
					this.items.find('.incomplete').parents('li').hide();
				},

				allWishesRoute: function(){
					wishRouter.navigate('',{trigger: true});
					this.items.find('li').show();
				}

			});

			/*********************************************************************/

			/**
			 * Bucketlist top level View
			 */

			var BucketListRouter = Backbone.Router.extend({

				routes: {
					'': 'all',
					'done': 'done',
					'remaining': 'remaining'
				},

				all: function(){
					console.log('showing all route');
				},

				done: function(){
					console.log('showing done route');
				},

				remaining: function(){
					console.log('showing remaining route');
				}

			});

			var wishRouter = new BucketListRouter();

			Backbone.history.start();

			$(document).ready(function(){
				var appview = new Appview();
			});

		</script>
	</body>
</html>