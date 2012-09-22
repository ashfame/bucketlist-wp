<html>
	<head>
		<title>Backbone.js sample app</title>
		<style>
			#bucketlist {
				margin: 20px;
				list-style: none;
				padding: 0;
			}
			#bucketlist li{
				padding:19px 19px 19px 70px;
				cursor:pointer;

			}
			.grey{background-color:#f0f0f0;}
			.incomplete{
				background:url('<?php bloginfo( 'template_directory' ); ?>/img/checkbox_unchecked.png') no-repeat 5px 4px;
			}
			.complete{
				background:url('<?php bloginfo( 'template_directory' ); ?>/img/checkbox_checked.png') no-repeat 5px 4px;
			}
		</style>
	</head>
	<body>
		<div id="playground">

		</div>
		<script src="<?php bloginfo( 'template_directory' ); ?>/js/jquery.min.js"></script>
		<script src="<?php bloginfo( 'template_directory' ); ?>/js/underscore-min.js"></script>
		<script src="<?php bloginfo( 'template_directory' ); ?>/js/backbone-min.js"></script>
		<script>

			/**
			 * Custom Backbone Sync method
			 */

			(function($) {
				Backbone.sync = function(method, model, options) {

					console.log('Backbone.sync() called:', method, model, options);
					//return false;
					//console.log( model instanceOf bucketListView );

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
			 * BucketListItem Model
			 */

			var BucketListItem = Backbone.Model.extend({

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
					console.log('initializing BucketListItem model');

					/* Add listeners here, when needed */

					this.on('change:status',function(){
						console.log('Bucketlist\'s status changed');
					});
					this.on('add',function(){
						this.save();
					});

					// Alert the error
					this.on('error',function(model,error){
						console.log(model,error);
					});
				},

				toggleStatus: function(){
					this.get('status') == 'incomplete' ? this.set({'status':'complete'}) : this.set({'status':'incomplete'});
				}

			});

			var bucketListItem = new BucketListItem({});
//			bucketListItem.set({
//				title: 'Do bungee jumping',
//				status: 'complete'
//			});


			/*********************************************************************/

			/**
			 * BucketList View
			 */

			var BucketListView = Backbone.View.extend({

				tagName: 'ul',
				id: 'bucketlist',
				className: 'grey',

				template: _.template('<li class="bucketListentry <%= status %>"><%= title %></li>'),

				events: {
					"click .bucketListentry": 'toggleStatus'
				},

				initialize: function(){
					this.model.on('change',this.render,this);
				},

				toggleStatus: function(){
					this.model.toggleStatus();
				},

				render: function(){
					this.$el.html(this.template(this.model.toJSON()));
				}

			});

			var bucketListView = new BucketListView({
				model: bucketListItem
			});

			bucketListView.render();

			//console.log(bucketListView.el);
			$('#playground').append(bucketListView.el);

			/*********************************************************************/

			/**
			 * BucketList Collection
			 */

			var BucketListCollection = Backbone.Collection.extend({
				model: BucketListItem
			});
			var bucketListCollection = new BucketListCollection();

			bucketListCollection.on('add',function(BucketListItem){
				console.log('Added ' + BucketListItem.get('title') + ' in collection');
			});


//			bucketListCollection.add([
//				{title: "Do a long Wheelie", status: "complete"},
//				{title: "Do a stoppie", status: "incomplete"},
//				{title: "Do BASE jumping", status: "incomplete"}
//			]);
//			bucketListCollection.add(bucketListItem);

			//bucketListCollection.url = '/bucketlistitems';
			bucketListCollection.fetch(); // will overwrite the collection contents

			/*********************************************************************/




		</script>
	</body>
</html>