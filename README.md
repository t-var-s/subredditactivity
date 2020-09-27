# subredditactivity
A simple script that collects every day the number of unique active users on a couple of subredits.
Originally written in a couple of hours in PHP. I'll accept pull requests if you'd like to implement the same thing in other languages.
In the initial implementation, the script is a daily cron job that looks at the top posts and comments of /r/dnd and /r/rpg.
The end result is a JSON object that can be used to later build some visualizations.
