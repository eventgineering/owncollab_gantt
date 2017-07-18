<?php
/**
 * @var OCP\Template $this
 * @var array $_
 */

$appName = 'owncollab_ganttchart';
?>

<div id="sidebar-save" class="sidebar-left">
    <div id="sidebar-export">
        <div class="tbl">
            <div class="tbl_cell export_gantt sidebar_header export_excel">
                <h4>Excel</h4>
		    <img src="<?php p($this->image_path($appName,'ms_excel.png'))?>" class="click" data-command="OCGantt.btnAction('export2excel')" alt="Export to Excel">
            </div>
            <div class="tbl_cell export_gantt sidebar_header export_pdf">
                <h4>PDF</h4>
                <img src="<?php p($this->image_path($appName,'application-pdf.png'))?>" class="click" data-command="OCGantt.btnAction('export2PDF')" alt="Export to PDF">
            </div>
            <div class="tbl_cell export_gantt sidebar_header export_img">
                <h4>Image</h4>
                <img src="<?php p($this->image_path($appName,'image.png'))?>" class="click" data-command="OCGantt.btnAction('export2image')" alt="Export to Image">
            </div>
            <div class="tbl_cell export_gantt sidebar_header export_ical">
                <h4>iCalendar</h4>
                <img src="<?php p($this->image_path($appName,'ical.png'))?>" class="click" data-command="OCGantt.btnAction('export2ical')" alt="Export to iCal-File">
            </div>
            <div class="tbl_cell export_gantt sidebar_header export_mc">
                <h4>MS Project</h4>
                <img src="<?php p($this->image_path($appName,'ms_project.png'))?>" class="click" data-command="OCGantt.btnAction('export2ms')" alt="Export to MS Project">
            </div>
        </div>
        <div id="sidebar-export-content-pdf" style="display: none">
            <form id="formExportToPDF">
                <p>Define period to export</p>
                    <div class="tbl">
                        <div class="tbl_cell"><span>Start</span> <input id="pdf_start_date" name="pdf_start_date" type="text" class="."></div>
                        <div class="tbl_cell txt_right"><span>End</span> <input id="pdf_end_date" name="pdf_end_date" type="text"></div>
                    </div>
                    <div class="tbl">
                        <div class="tbl_cell width20">Paper size</div>
                        <div class="tbl_cell">
                            <select id= "pdf_paper_size" name="pdf_paper_size">
                                <option value="A0">A0 - 118.9 x 84.1 cm</option>
                                <option value="A1">A1 - 84.1 x 59.4 cm</option>
                                <option value="A2">A2 - 59.4 x 42 cm</option>
                                <option value="A3">A3 - 42 x 29.7 cm</option>
                                <option value="A4" selected>A4 - 29.7 x 21 cm</option>
                                <option value="A5">A5 - 21 x 14.8 cm</option>
                            </select>
                        </div>
                    </div>
                    <div class="tbl">
                        <div class="tbl_cell width20">Orientation</div>
                        <div class="tbl_cell">
                            <select name="pdf_paper_orientation" id="pdf_paper_orientation">
                                <option value="Portrait">Portrait</option>
                                <option value="Landscape">Landscape</option>
                            </select>
                        </div>
                    </div>
                    <p>Define Header</p>
                    <div class="tbl">
                        <div class="tbl_cell width20">Left</div>
                        <div class="tbl_cell"><input name="pdf_header_left" id="pdf_header_left" type="text"></div>
                    </div>
                    <div class="tbl">
                        <div class="tbl_cell width20">Center</div>
                        <div class="tbl_cell"><input name="pdf_header_center" id="pdf_header_center" type="text"></div>
                    </div>
                    <div class="tbl">
                        <div class="tbl_cell width20">Right</div>
                        <div class="tbl_cell"><input name="pdf_header_right" id="pdf_header_right" type="text"></div>
                    </div>
                    <p>Define Footer</p>
                    <div class="tbl">
                        <div class="tbl_cell width20">Left</div>
                        <div class="tbl_cell"><input name="pdf_footer_left" id="pdf_footer_left" type="text"></div>
                    </div>
                    <div class="tbl">
                        <div class="tbl_cell width20">Center</div>
                        <div class="tbl_cell"><input name="pdf_footer_center" id="pdf_footer_center" type="text"></div>
                    </div>
                    <div class="tbl">
                        <div class="tbl_cell width20">Right</div>
                        <div class="tbl_cell"><input name="pdf_footer_right" id="pdf_footer_right" type="text"></div>
                    </div>
                    <div>
                        <input type="button" id="submit_export_pdf" value="Export"> <div class="export_loader loader_min" style="display: none"></div>
                        <i id="save-export" class="fa fa-spinner fa-pulse" style="display: none;"></i>
                        <p id="exporting-to-pdf" style="display:none;">We are preparing your PDF file. This may take a while.</p>
                    </div>
            </form>
        </div>
    </div>
</div>
