# make file for organizing and testing the project
# used with Make::SSH, not normal make


SSH_SERVER = 192.168.11.3:22

USER = dmin
PASSWORD = dminpassword

APACHE_LOG = /var/log/apache2/error.log

PLUGIN_NAME = test-plugin
WP_DIRECTORY = /var/www/html/wp
WP_SERVER = http://192.168.11.3/wp

WP_ADMIN = dmin
WP_ADMIN_PASSWORD = dminpassword

upload:
	sftp $(USER):$(PASSWORD)@$(SSH_SERVER)
		delete $(WP_DIRECTORY)/wp-content/plugins/$(PLUGIN_NAME)
		put $(PLUGIN_NAME) => $(WP_DIRECTORY)/wp-content/plugins/$(PLUGIN_NAME)

get_log:
	sftp $(USER):$(PASSWORD)@$(SSH_SERVER)
		get $(APACHE_LOG) => error.log

tail_log:
	ssh $(USER):$(PASSWORD)@$(SSH_SERVER)
		tail -n 40 $(APACHE_LOG)

test:
	sh
		cd tests
		./test.pl $(WP_SERVER)/ $(WP_ADMIN) $(WP_ADMIN_PASSWORD)

clean:
	sh
		rm -f error.log
