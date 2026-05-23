# ══════════════════════════════════════════════════════════════════════════════
# Makefile — spotify_cm · Despliegue automatizado por bloques
# ══════════════════════════════════════════════════════════════════════════════
#
# USO RÁPIDO:
#   make deploy-all      → Despliegue completo (esquema + seed + web)
#   make db-schema       → Solo crea el esquema y los índices
#   make db-seed         → Solo ejecuta el ETL (carga de datos)
#   make deploy-web      → Solo despliega la app PHP en Apache
#   make status          → Estado del servidor remoto
#   make clean-remote    → Borra la app del servidor
#   make help            → Muestra esta ayuda
#
# REQUISITOS:
#   - ssh / scp disponibles en PATH (Git Bash en Windows)
#   - Python 3 con pip (para db-seed)
#   - Clave SSH configurada en SSH_KEY
#   - etl/.env configurado con las credenciales de BD
# ══════════════════════════════════════════════════════════════════════════════

# ── Conexión SSH ──────────────────────────────────────────────────────────────
SSH_KEY    := C:/Users/Usuario/ssh-key-2026-03-04.key
SERVER_IP  := 79.72.55.215
SSH_USER   := opc
SSH        := ssh -i "$(SSH_KEY)" -o StrictHostKeyChecking=no $(SSH_USER)@$(SERVER_IP)
SCP        := scp -i "$(SSH_KEY)" -o StrictHostKeyChecking=no

# ── Base de datos (en el servidor) ────────────────────────────────────────────
DB_USER    := root
DB_PASS    := mycontGI_7_6
DB_NAME    := spotify_cm
MYSQL_CMD  := mysql -u$(DB_USER) -p'$(DB_PASS)'

# ── Rutas locales (relativas a este Makefile) ─────────────────────────────────
SCRIPTS    := data_model/scripts_bd
ETL        := etl
WEB        := spotify_cm

# ── Rutas remotas ─────────────────────────────────────────────────────────────
REMOTE_APP   := /var/www/html/spotify_cm
REMOTE_STAGE := /home/$(SSH_USER)/deploy_stage

# ── Colores para output ───────────────────────────────────────────────────────
GREEN  := \033[0;32m
YELLOW := \033[0;33m
BLUE   := \033[0;34m
RESET  := \033[0m

.PHONY: all help deploy-all db-schema db-seed deploy-web status clean-remote

# ── Ayuda (target por defecto) ────────────────────────────────────────────────
all: help

help:
	@echo ""
	@echo "  $(BLUE)spotify_cm — Despliegue automatizado$(RESET)"
	@echo ""
	@echo "  $(YELLOW)Bloques individuales:$(RESET)"
	@echo "    make db-schema      Crea el esquema BD y aplica índices"
	@echo "    make db-seed        Ejecuta el ETL (carga ~2 M registros)"
	@echo "    make deploy-web     Sube y configura la app PHP en Apache"
	@echo ""
	@echo "  $(YELLOW)Despliegue completo:$(RESET)"
	@echo "    make deploy-all     Ejecuta db-schema + db-seed + deploy-web"
	@echo ""
	@echo "  $(YELLOW)Utilidades:$(RESET)"
	@echo "    make status         Estado de servicios y disco en el servidor"
	@echo "    make clean-remote   Elimina la app del servidor"
	@echo ""
	@echo "  $(YELLOW)Servidor:$(RESET) http://$(SERVER_IP)/spotify_cm/"
	@echo ""

# ══════════════════════════════════════════════════════════════════════════════
# BLOQUE 1 — Esquema de base de datos
# ══════════════════════════════════════════════════════════════════════════════
db-schema:
	@echo "$(BLUE)▶ [BD 1/3] Creando esquema optimizado...$(RESET)"
	$(SSH) "$(MYSQL_CMD) < /dev/stdin" \
		< $(SCRIPTS)/script_creacion_bd_optimizada.sql
	@echo "$(BLUE)▶ [BD 2/3] Aplicando índices de rendimiento...$(RESET)"
	$(SSH) "$(MYSQL_CMD) $(DB_NAME) < /dev/stdin" \
		< $(SCRIPTS)/script_indices.sql
	@echo "$(BLUE)▶ [BD 3/3] Aplicando correcciones de datos...$(RESET)"
	$(SSH) "$(MYSQL_CMD) $(DB_NAME) < /dev/stdin" \
		< $(SCRIPTS)/script_correccion_canciones.sql
	@echo "$(GREEN)✓ Esquema listo$(RESET)"

# ══════════════════════════════════════════════════════════════════════════════
# BLOQUE 2 — ETL: carga de datos
# Copia los scripts al servidor y los ejecuta allí (MySQL es localhost en remoto)
# ══════════════════════════════════════════════════════════════════════════════
db-seed:
	@echo "$(BLUE)▶ [ETL 1/3] Copiando scripts ETL al servidor...$(RESET)"
	$(SSH) "mkdir -p /home/$(SSH_USER)/etl_tmp"
	$(SCP) -r $(ETL)/* $(SSH_USER)@$(SERVER_IP):/home/$(SSH_USER)/etl_tmp/
	@echo "$(BLUE)▶ [ETL 2/3] Instalando dependencias Python en el servidor...$(RESET)"
	$(SSH) "cd /home/$(SSH_USER)/etl_tmp && pip3 install -r requirements.txt -q"
	@echo "$(BLUE)▶ [ETL 3/3] Ejecutando orquestador (puede tardar ~30 min)...$(RESET)"
	$(SSH) "cd /home/$(SSH_USER)/etl_tmp && python3 orquestador.py"
	@echo "$(BLUE)▶ Limpiando archivos temporales...$(RESET)"
	$(SSH) "rm -rf /home/$(SSH_USER)/etl_tmp"
	@echo "$(GREEN)✓ ETL completado$(RESET)"

# ══════════════════════════════════════════════════════════════════════════════
# BLOQUE 3 — Despliegue de la aplicación web PHP
# ══════════════════════════════════════════════════════════════════════════════
deploy-web:
	@echo "$(BLUE)▶ [Web 1/3] Preparando directorio de staging...$(RESET)"
	$(SSH) "mkdir -p $(REMOTE_STAGE)/panel $(REMOTE_STAGE)/visualizaciones"

	@echo "$(BLUE)▶ [Web 2/3] Subiendo ficheros PHP...$(RESET)"
	$(SCP) $(WEB)/index.php    $(SSH_USER)@$(SERVER_IP):$(REMOTE_STAGE)/
	$(SCP) $(WEB)/Conexion.php $(SSH_USER)@$(SERVER_IP):$(REMOTE_STAGE)/
	$(SCP) $(WEB)/panel/MantenimientoModel.php      \
	       $(WEB)/panel/MantenimientoController.php \
	       $(WEB)/panel/MantenimientoView.php        \
	       $(SSH_USER)@$(SERVER_IP):$(REMOTE_STAGE)/panel/
	$(SCP) $(WEB)/visualizaciones/VisualizacionesModel.php      \
	       $(WEB)/visualizaciones/VisualizacionesController.php \
	       $(WEB)/visualizaciones/VisualizacionesView.php        \
	       $(SSH_USER)@$(SERVER_IP):$(REMOTE_STAGE)/visualizaciones/
	$(SCP) $(WEB)/.htaccess \
	       $(SSH_USER)@$(SERVER_IP):$(REMOTE_STAGE)/.htaccess

	@echo "$(BLUE)▶ [Web 3/3] Configurando Apache, permisos y SELinux...$(RESET)"
	$(SSH) " \
		sudo mkdir -p $(REMOTE_APP)/panel $(REMOTE_APP)/visualizaciones && \
		sudo mv $(REMOTE_STAGE)/index.php    $(REMOTE_APP)/ && \
		sudo mv $(REMOTE_STAGE)/Conexion.php $(REMOTE_APP)/ && \
		sudo mv $(REMOTE_STAGE)/.htaccess    $(REMOTE_APP)/ && \
		sudo mv $(REMOTE_STAGE)/panel/*.php  $(REMOTE_APP)/panel/ && \
		sudo mv $(REMOTE_STAGE)/visualizaciones/*.php $(REMOTE_APP)/visualizaciones/ && \
		sudo rm -rf $(REMOTE_STAGE) && \
		sudo chmod -R 644 $(REMOTE_APP) && \
		sudo chmod 755 $(REMOTE_APP) \
		              $(REMOTE_APP)/panel \
		              $(REMOTE_APP)/visualizaciones && \
		sudo restorecon -Rv $(REMOTE_APP)/ && \
		sudo systemctl reload httpd \
	"
	@echo "$(GREEN)✓ App desplegada en http://$(SERVER_IP)/spotify_cm/$(RESET)"

# ══════════════════════════════════════════════════════════════════════════════
# DESPLIEGUE COMPLETO
# ══════════════════════════════════════════════════════════════════════════════
deploy-all: db-schema db-seed deploy-web
	@echo ""
	@echo "$(GREEN)══════════════════════════════════════════$(RESET)"
	@echo "$(GREEN)  ✓ Despliegue completo finalizado$(RESET)"
	@echo "$(GREEN)  → http://$(SERVER_IP)/spotify_cm/$(RESET)"
	@echo "$(GREEN)══════════════════════════════════════════$(RESET)"
	@echo ""

# ══════════════════════════════════════════════════════════════════════════════
# UTILIDADES
# ══════════════════════════════════════════════════════════════════════════════
status:
	@echo "$(BLUE)▶ Estado del servidor $(SERVER_IP)...$(RESET)"
	$(SSH) " \
		echo '--- Servicios ---' && \
		systemctl is-active httpd && \
		systemctl is-active mysqld && \
		echo '--- Disco ---' && \
		df -h / | tail -1 && \
		echo '--- App web ---' && \
		ls $(REMOTE_APP)/ 2>/dev/null || echo 'App no desplegada' && \
		echo '--- BD ---' && \
		$(MYSQL_CMD) -e \
		  'SELECT table_name, table_rows \
		   FROM information_schema.tables \
		   WHERE table_schema=\"$(DB_NAME)\" \
		   ORDER BY table_rows DESC;' 2>/dev/null \
	"

clean-remote:
	@echo "$(YELLOW)▶ Eliminando app del servidor...$(RESET)"
	$(SSH) "sudo rm -rf $(REMOTE_APP)"
	@echo "$(GREEN)✓ App eliminada$(RESET)"
