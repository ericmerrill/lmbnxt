Things to migrate
# Settings


# Settings Done - Needs docs
- logtolocation > logpath
- bannerxmllocation > xmlpath
- bannerxmlfolder > extractpath
- ignoreemailcase > lowercaseemails
- ignoreusernamecase > (delete)
- usernamesource (moved to constants)
- customfield1source (moved to constants)
- cattype (moved to constants)
- xlstype (moved to constants)
- forcename split to forcefirstname and forcelastname
- logerrors (convert to menu choice)
- coursehidden (Moved to constants)




# Docs
* Assumes that if there is a `<?xml` or `<!DOCTYPE` in the start of the doc, it is well formed
* Otherwise, it wraps the input in <lmb> tags to make sure it works correctly
* ignoreusernamecaseis now assumed
* Setting changes from above

# Object types
* Add additional status columns (crosslists, course, etc)
* Make additional always save in same order...
* Add term column to enrol

# Upgrade
* Migrate term messages
* Improve migration process
* Delete old tables


# Features to add
* Progress object for parsing
* Use MD5 to see if we have run a file, not timestamp
* Option to reprocess from DB when settings change
* Settings controller/change settings based on input
* Use internal ID instead of SDID or G# in ID Number
* Quickly check if enrolments match expected?

# LIS 2 improvements
* LIS group term allows restrict flag, even though ILP doesn't seem to use
* Deal with possible namespaces in XML
* A person MembershipRequest technically allows multiple role fields
* Improve role type conversions and make into settings.
* Possible option to 'match' sources between LMB and LIS.

# Moodle
* Deal with users that have more than one role assignment in a course.
* Handle term name changes

# Misc
* Doc mappings.json
* General dev docs
* Deal with SourceDID vs SpridenID (sdid vs G#)
* Drop sourcedidsource requirements.
* Add caching in key places? Like term lookups
* When receiving a new message, like a person, check to see if we need to processes any missed enrollments
* Possibly track if enrolment was successful in DB like old LMB

