# project directory
chdir = '/www/wwwroot/3086311361.rplkraksaan.my.id/project-presensi/python_face_api'

# worker processes
workers = 4

# threads per worker
threads = 2

# user to run as
user = 'www'

# worker type
worker_class = 'sync'

# bind IP and port
bind = '0.0.0.0:5002'

# PID file path (used for stopping and restarting; do not remove)
pidfile = '/www/wwwroot/3086311361.rplkraksaan.my.id/project-presensi/python_face_api/gunicorn.pid'

# access log and error log paths, file name do not change 'gunicorn_acess.log', 'gunicorn_error.log'
accesslog = '/www/wwwlogs/python/python_face_api/gunicorn_acess.log'
errorlog = '/www/wwwlogs/python/python_face_api/gunicorn_error.log'

# Log level (this log level applies to the error log; the access log level cannot be configured)
# debug: Debug level;
# info: Normal level;
# warning: Warning messages level;
# error: Error level;
# critical: Critical errors;
loglevel = 'info'

# Put custom settings here
# It is best to follow the same format as above: <comment + newline + key = value>.
# PS: Gunicorn configuration files are Python\-style (i.e., ".py" files); make sure to follow Python syntax.
# For example: if a config value (such as loglevel) is a string, it must be enclosed in quotes.